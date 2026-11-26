'use strict';

const http = require('http');
const crypto = require('crypto');
const { WebSocketServer } = require('ws');
const mysql = require('mysql2/promise');

const PORT = Number(process.env.PORT || 8090);
const SECRET = String(process.env.WS_SECRET || '');
const DB = {
  host: process.env.DB_HOST || 'cms-db',
  port: Number(process.env.DB_PORT || 3306),
  user: process.env.DB_USER || '',
  password: process.env.DB_PASSWORD || '',
  database: process.env.DB_NAME || '',
  waitForConnections: true,
  connectionLimit: 5,
  timezone: 'Z',
};

if (!SECRET) {
  console.error('WS_SECRET is required');
  process.exit(1);
}

const pool = mysql.createPool(DB);

function hashToken(token) {
  return crypto.createHash('sha256').update(String(token)).digest('hex');
}

function safeEqual(left, right) {
  const a = Buffer.from(String(left));
  const b = Buffer.from(String(right));
  return a.length === b.length && crypto.timingSafeEqual(a, b);
}

async function authenticate(token) {
  if (!token) return null;
  const [rows] = await pool.execute(
    'SELECT d.user_id, u.display_name FROM sek_device d '
      + 'INNER JOIN sek_user u ON u.user_id = d.user_id '
      + 'WHERE d.access_token_hash = ? AND d.revoked_at IS NULL '
      + 'AND d.access_expires_at > UTC_TIMESTAMP() AND u.status = "active" LIMIT 1',
    [hashToken(token)]
  );
  return rows.length === 1 ? rows[0] : null;
}

async function isMember(conversationId, userId) {
  const [rows] = await pool.execute(
    'SELECT 1 FROM sek_conversation_member WHERE conversation_id = ? AND user_id = ? '
      + 'AND left_at IS NULL LIMIT 1',
    [conversationId, userId]
  );
  return rows.length === 1;
}

function send(ws, payload) {
  if (ws.readyState === 1) {
    ws.send(JSON.stringify(payload));
  }
}

const server = http.createServer(async (req, res) => {
  if (req.method === 'GET' && req.url === '/health') {
    res.writeHead(200, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify({ status: 'ok' }));
    return;
  }

  if (req.method === 'POST' && req.url === '/publish') {
    if (!safeEqual(req.headers['x-internal-secret'] || '', SECRET)) {
      res.writeHead(403, { 'Content-Type': 'application/json' });
      res.end(JSON.stringify({ error: 'forbidden' }));
      return;
    }
    let raw = '';
    req.setEncoding('utf8');
    req.on('data', (chunk) => {
      raw += chunk;
      if (raw.length > 1048576) {
        req.destroy();
      }
    });
    req.on('end', () => {
      let body;
      try {
        body = JSON.parse(raw || '{}');
      } catch (error) {
        res.writeHead(400, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ error: 'invalid_json' }));
        return;
      }
      const conversationId = Number(body.conversation_id || 0);
      if (!conversationId || !body.event) {
        res.writeHead(422, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ error: 'conversation_id_and_event_required' }));
        return;
      }
      const excluded = Number(body.exclude_user_id || 0);
      const event = {
        event: String(body.event),
        conversation_id: conversationId,
        payload: body.payload || null,
        sent_at: new Date().toISOString(),
      };
      for (const client of wss.clients) {
        if (client.authenticated && client.subscriptions.has(conversationId)
          && (!excluded || client.userId !== excluded)) {
          send(client, event);
        }
      }
      res.writeHead(202, { 'Content-Type': 'application/json' });
      res.end(JSON.stringify({ status: 'published' }));
    });
    return;
  }

  res.writeHead(404, { 'Content-Type': 'application/json' });
  res.end(JSON.stringify({ error: 'not_found' }));
});

const wss = new WebSocketServer({ server });

wss.on('connection', (ws) => {
  ws.authenticated = false;
  ws.userId = null;
  ws.subscriptions = new Set();
  ws.isAlive = true;

  ws.on('pong', () => { ws.isAlive = true; });

  const authTimer = setTimeout(() => {
    if (!ws.authenticated) ws.close(4001, 'auth_timeout');
  }, 10000);

  ws.on('message', async (raw) => {
    let message;
    try {
      message = JSON.parse(String(raw));
    } catch (error) {
      send(ws, { event: 'error', code: 'invalid_json' });
      return;
    }
    const type = String(message.type || '');

    if (type === 'auth') {
      try {
        const user = await authenticate(message.token);
        if (!user) {
          ws.close(4003, 'unauthenticated');
          return;
        }
        clearTimeout(authTimer);
        ws.authenticated = true;
        ws.userId = Number(user.user_id);
        send(ws, {
          event: 'ready',
          user_id: ws.userId,
          display_name: user.display_name,
        });
      } catch (error) {
        console.error('auth failed', error.message);
        ws.close(1011, 'server_error');
      }
      return;
    }

    if (!ws.authenticated) {
      ws.close(4003, 'unauthenticated');
      return;
    }

    if (type === 'subscribe' || type === 'unsubscribe') {
      const conversationId = Number(message.conversation_id || 0);
      if (!conversationId) {
        send(ws, { event: 'error', code: 'conversation_id_required' });
        return;
      }
      try {
        if (type === 'subscribe') {
          if (!(await isMember(conversationId, ws.userId))) {
            send(ws, { event: 'error', code: 'forbidden', conversation_id: conversationId });
            return;
          }
          ws.subscriptions.add(conversationId);
          send(ws, { event: 'subscribed', conversation_id: conversationId });
        } else {
          ws.subscriptions.delete(conversationId);
          send(ws, { event: 'unsubscribed', conversation_id: conversationId });
        }
      } catch (error) {
        console.error('subscription failed', error.message);
        send(ws, { event: 'error', code: 'server_error' });
      }
      return;
    }

    if (type === 'ping') {
      send(ws, { event: 'pong' });
    }
  });
});

const heartbeat = setInterval(() => {
  for (const ws of wss.clients) {
    if (!ws.isAlive) {
      ws.terminate();
      continue;
    }
    ws.isAlive = false;
    ws.ping();
  }
}, 30000);

wss.on('close', () => clearInterval(heartbeat));

server.listen(PORT, () => {
  console.log(`Sekitar WS gateway listening on :${PORT}`);
});

async function shutdown() {
  clearInterval(heartbeat);
  server.close();
  await pool.end().catch(() => {});
  process.exit(0);
}

process.on('SIGTERM', shutdown);
process.on('SIGINT', shutdown);
