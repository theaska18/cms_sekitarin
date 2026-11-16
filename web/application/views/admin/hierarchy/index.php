<?php defined('BASEPATH') OR exit('No direct script access allowed');
$org_by_id = array();
$children_by_parent = array();
$has_parent = array();
foreach ($hierarchy_rows as $org) { $org_by_id[(int)$org['org_id']] = $org; }
foreach ($org_by_id as $org_id => $org) {
    $parent_id = (int)$org['parent_org'];
    if ($parent_id && isset($org_by_id[$parent_id])) { $children_by_parent[$parent_id][] = $org_id; $has_parent[$org_id] = TRUE; }
}
$rendered_ids = array();
$render_node = NULL;
$render_node = function ($org_id, $level = 1) use (&$render_node, &$rendered_ids, $org_by_id, $children_by_parent, $hierarchy_can_update, $hierarchy_can_add, $hierarchy_can_delete, $labels) {
    if (!isset($org_by_id[$org_id]) || isset($rendered_ids[$org_id])) { return; }
    $rendered_ids[$org_id] = TRUE;
    $org = $org_by_id[$org_id];
    $children = isset($children_by_parent[$org_id]) ? $children_by_parent[$org_id] : array();
    $name = trim((string)$org['org_name']);
    $tools = '';
    if ($hierarchy_can_update || $hierarchy_can_add || ($hierarchy_can_delete && !$children)) {
        $menu_items = '';
        if ($hierarchy_can_add) {
            $menu_items .= '<a href="/admin/hr/hierarchy/new?parent_org='.(int)$org_id.'" data-parent-org="'.(int)$org_id.'"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M10 3v14M3 10h14"/></svg><span>'.html_escape($labels['hierarchy_add_child']).'</span></a>';
        }
        if ($hierarchy_can_update) {
            $menu_items .= '<a href="/admin/hr/hierarchy/new?org_id='.(int)$org_id.'" data-edit-org="'.(int)$org_id.'"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="m4 13 9-9 3 3-9 9-4 1 1-4Z"/></svg><span>'.html_escape($labels['hierarchy_edit']).'</span></a>';
        }
        if ($hierarchy_can_delete && !$children) {
            $menu_items .= '<button type="button" data-delete-org="'.(int)$org_id.'" data-delete-name="'.html_escape($name).'" class="is-danger"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M4 6h12m-10 0 1 11h6l1-11M8 6V4h4v2"/></svg><span>'.html_escape($labels['hierarchy_delete']).'</span></button>';
        }
        $tools = '<div class="hierarchy-tree-table__tools"><button type="button" class="hierarchy-tree-table__menu-button" aria-label="'.html_escape(sprintf($labels['hierarchy_menu_aria'], $name)).'" aria-expanded="false" title="'.html_escape($labels['hierarchy_menu_title']).'"><svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><circle cx="10" cy="4" r="1.6"/><circle cx="10" cy="10" r="1.6"/><circle cx="10" cy="16" r="1.6"/></svg></button><div class="org-chart-card__menu hierarchy-tree-table__action-menu" hidden>'.$menu_items.'</div></div>';
    }
    ?>
    <tr role="row" data-org-card data-org-id="<?= (int)$org_id ?>" data-tree-row data-level="<?= (int)$level ?>" aria-level="<?= (int)$level ?>"<?= $children ? ' aria-expanded="true"' : '' ?>>
      <td role="gridcell" class="hierarchy-tree-table__unit"><div class="hierarchy-tree-table__indent" style="--tree-level:<?= (int)$level - 1 ?>"><span class="hierarchy-tree-table__branch" aria-hidden="true"></span><?php if (!$children): ?><span class="hierarchy-tree-table__leaf" aria-hidden="true"></span><?php endif; ?><?= $tools ?><span role="treeitem" aria-level="<?= (int)$level ?>" class="hierarchy-tree-table__name"><?= html_escape($name) ?></span></div></td>
      <td role="gridcell" class="hierarchy-tree-table__code"><?= html_escape($org['org_code']) ?></td>
      <td role="gridcell"><?= !empty($org['head_employee_name']) ? html_escape($org['head_employee_name']) : '<span class="hierarchy-tree-table__muted">'.html_escape($labels['hierarchy_unassigned']).'</span>' ?></td>
      <td role="gridcell" class="hierarchy-tree-table__members"><?= !empty($org['member_names']) ? html_escape($org['member_names']) : '—' ?></td>
      
    </tr>
    <?php if ($children): ?><?php $child_ids = $children; ?><tr role="rowgroup" data-tree-children="<?= (int)$org_id ?>"><?php foreach ($child_ids as $child_id): ?><?php $render_node($child_id, $level + 1); ?><?php endforeach; ?></tr><?php endif; ?>
    <?php
};
$root_ids = array();
foreach ($org_by_id as $org_id => $org) { if (!isset($has_parent[$org_id])) { $root_ids[] = $org_id; } }
?>
<main class="-mx-4 w-[calc(100%+2rem)] px-4 py-6 sm:mx-auto sm:w-full sm:max-w-7xl sm:px-6 sm:py-8 lg:px-8">
    <nav aria-label="Breadcrumb" class="mb-5 text-sm text-slate-500">
        <ol class="flex flex-wrap items-center gap-2">
            <li><?= html_escape($labels['hr']) ?></li>
            <li aria-hidden="true">/</li>
            <li aria-current="page" class="font-medium text-slate-800"><?= html_escape($labels['hierarchy_title']) ?></li>
        </ol>
    </nav>

    <header class="mb-8 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div class="max-w-2xl">
            <h1 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl"><?= html_escape($labels['hierarchy_title']) ?></h1>
            <p class="mt-2 text-base leading-7 text-slate-600"><?= html_escape($labels['hierarchy_intro']) ?></p>
        </div>
        <?php if ($hierarchy_can_add): ?>
            <a href="/admin/hr/hierarchy/new" class="inline-flex h-11 shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-xl bg-indigo-600 px-4 py-0 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
<svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-5 w-5"><path d="M10 4a.75.75 0 0 1 .75.75v4.5h4.5a.75.75 0 0 1 0 1.5h-4.5v4.5a.75.75 0 0 1-1.5 0v-4.5h-4.5a.75.75 0 0 1 0-1.5h4.5v-4.5A.75.75 0 0 1 10 4Z"/></svg>
                <?= html_escape($labels['hierarchy_add']) ?>
            </a>
        <?php endif; ?>
    </header>
  <?php if (!$hierarchy_available): ?><div role="status" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-900"><?= html_escape($labels['hierarchy_unavailable']) ?></div><?php else: ?>
    <?php if ($hierarchy_status): ?><div role="status" class="mb-5 rounded-xl border border-slate-200 bg-slate-50 p-4"><?= html_escape($hierarchy_status === 'created' ? $labels['hierarchy_status_created'] : ($hierarchy_status === 'deleted' ? $labels['hierarchy_status_deleted'] : ($hierarchy_status === 'saved' ? $labels['hierarchy_status_saved'] : $labels['hierarchy_status_error']))) ?></div><?php endif; ?>
    <section aria-labelledby="org-chart-heading" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div class="hierarchy-tree-table__heading flex flex-col gap-2 border-b border-slate-200 bg-slate-50/70 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"><div><h2 id="org-chart-heading" class="font-semibold text-slate-900"><?= html_escape($labels['hierarchy_list_title']) ?></h2><p class="mt-1 text-sm text-slate-500"><?= html_escape($labels['hierarchy_list_hint']) ?></p></div><span class="inline-flex w-fit items-center gap-2 text-xs text-slate-500"><span class="h-2 w-2 rounded-full bg-primary-500"></span><?= html_escape($labels['hierarchy_legend']) ?></span></div>
      <?php if (!$org_by_id): ?><div class="px-5 py-14 text-center"><p class="font-medium text-slate-800"><?= html_escape($labels['hierarchy_empty']) ?></p><p class="mt-1 text-sm text-slate-500"><?= html_escape($labels['hierarchy_empty_hint']) ?></p></div>
      <?php else: ?><div class="hierarchy-tree-table-wrap" role="region" aria-label="<?= html_escape($labels['hierarchy_list_title']) ?>" tabindex="0"><table class="hierarchy-tree-table" role="treegrid" aria-labelledby="org-chart-heading" aria-colcount="4"><thead role="rowgroup"><tr role="row"><th role="columnheader" scope="col"><?= html_escape($labels['hierarchy_unit']) ?></th><th role="columnheader" scope="col"><?= html_escape($labels['hierarchy_code']) ?></th><th role="columnheader" scope="col"><?= html_escape($labels['hierarchy_head']) ?></th><th role="columnheader" scope="col"><?= html_escape($labels['hierarchy_members']) ?></th></tr></thead><tbody role="rowgroup"><?php foreach ($root_ids as $root_id): ?><?php $render_node($root_id); ?><?php endforeach; ?><?php foreach ($org_by_id as $org_id => $org): ?><?php if (!isset($rendered_ids[$org_id])): ?><?php $render_node($org_id); ?><?php endif; ?><?php endforeach; ?></tbody></table></div><?php endif; ?>
    </section>
  <?php endif; ?>
</main>
<?php if ($hierarchy_can_delete): ?><dialog id="org-delete-dialog" class="org-delete-dialog" data-delete-body-template="<?= html_escape($labels['hierarchy_delete_body']) ?>" data-csrf-refresh-url="/admin/hr/hierarchy/csrf_token"><form method="post" action="/admin/hr/hierarchy/action"><input type="hidden" name="<?= html_escape($csrf_token_name) ?>" value="<?= html_escape($csrf_hash) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="org_id" value=""><h2 class="text-lg font-semibold"><?= html_escape($labels['hierarchy_delete_title']) ?></h2><p class="my-3 text-sm text-slate-600" data-delete-message></p><p hidden role="status" class="mb-3 text-sm text-rose-700" data-delete-error><?= html_escape($labels['hierarchy_csrf_refresh_error']) ?></p><div class="flex justify-end gap-2"><button type="button" data-close-dialog class="rounded-lg border px-4 py-2"><?= html_escape($labels['hierarchy_cancel']) ?></button><button type="submit" data-delete-submit class="rounded-lg bg-rose-700 px-4 py-2 font-semibold text-white"><?= html_escape($labels['hierarchy_confirm_delete']) ?></button></div></form></dialog>
<script>
(()=>{
  const root=document.querySelector('.hierarchy-tree-table');
 const menus=new Map();
 const closeMenu=(menu,button)=>{if(!menu)return;menu.hidden=true;button?.setAttribute('aria-expanded','false');const marker=menus.get(menu);if(marker){marker.parentNode?.insertBefore(menu,marker);marker.remove();menus.delete(menu);}};
 const placeMenu=(menu,button)=>{const r=button.getBoundingClientRect();menu.style.position='fixed';menu.style.zIndex='99999';menu.style.left=Math.max(8,Math.min(r.right-menu.offsetWidth,innerWidth-menu.offsetWidth-8))+'px';const below=innerHeight-r.bottom-8;const above=r.top-8;menu.style.top=(below>=menu.offsetHeight||below>=above?r.bottom+4:Math.max(8,r.top-menu.offsetHeight-4))+'px';};
 root?.querySelectorAll('[data-org-card]').forEach(card=>{
  const menuBtn=card.querySelector('.hierarchy-tree-table__menu-button');
  const menu=menuBtn?.nextElementSibling;
  if(!menuBtn||!menu?.matches('.org-chart-card__menu'))return;
  menuBtn.addEventListener('click',e=>{e.stopPropagation();const open=menu.hidden;document.querySelectorAll('.org-chart-card__menu').forEach(m=>{if(m!==menu){const b=m.__hierarchyTrigger;closeMenu(m,b);}});if(open){const marker=menu.ownerDocument.createComment('menu-position');menu.before(marker);menus.set(menu,marker);menu.__hierarchyTrigger=menuBtn;document.body.appendChild(menu);menu.hidden=false;menuBtn.setAttribute('aria-expanded','true');placeMenu(menu,menuBtn);}});
 });
 document.addEventListener('click',e=>{document.querySelectorAll('.org-chart-card__menu').forEach(m=>{const b=m.__hierarchyTrigger;if(!m.hidden&&!m.contains(e.target)&&!b?.contains(e.target))closeMenu(m,b);});});
 addEventListener('resize',()=>document.querySelectorAll('.org-chart-card__menu').forEach(m=>{if(!m.hidden&&m.__hierarchyTrigger)placeMenu(m,m.__hierarchyTrigger);}));
   const dlg=document.getElementById('org-delete-dialog');if(dlg){document.querySelectorAll('[data-delete-org]').forEach(b=>b.addEventListener('click',()=>{dlg.querySelector('[name="org_id"]').value=b.dataset.deleteOrg;dlg.querySelector('[data-delete-message]').textContent=dlg.dataset.deleteBodyTemplate.replace('%s',b.dataset.deleteName);dlg.querySelector('[data-delete-error]').hidden=true;dlg.showModal();}));dlg.querySelector('[data-close-dialog]')?.addEventListener('click',()=>dlg.close());const deleteForm=dlg.querySelector('form'),csrf=deleteForm.querySelector('input[name="<?= html_escape($csrf_token_name) ?>"]'),submit=deleteForm.querySelector('[data-delete-submit]');deleteForm.addEventListener('submit',event=>{if(deleteForm.dataset.csrfReady==='true'){delete deleteForm.dataset.csrfReady;return;}event.preventDefault();submit.disabled=true;fetch(dlg.dataset.csrfRefreshUrl,{credentials:'same-origin',headers:{'Accept':'application/json'}}).then(response=>{if(!response.ok)throw new Error('CSRF refresh failed');return response.json();}).then(tokens=>{if(!tokens.name||!tokens.hash)throw new Error('CSRF token missing');csrf.name=tokens.name;csrf.value=tokens.hash;deleteForm.dataset.csrfReady='true';submit.disabled=false;deleteForm.requestSubmit(submit);}).catch(()=>{dlg.querySelector('[data-delete-error]').hidden=false;}).finally(()=>{submit.disabled=false;});});}
 
})();

</script><?php endif; ?>
