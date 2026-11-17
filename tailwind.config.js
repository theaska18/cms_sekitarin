module.exports = {
  content: ['./web/application/views/**/*.php', './web/assets/js/*.js'],
  theme: {
    extend: {
      colors: {
        ink: '#080d1b',
        panel: '#10182b',
        violetglow: '#8b5cf6',
        cyanbright: '#22d3ee'
      },
      boxShadow: {
        glow: '0 20px 80px rgba(99, 102, 241, .22)'
      }
    }
  },
  plugins: []
};
