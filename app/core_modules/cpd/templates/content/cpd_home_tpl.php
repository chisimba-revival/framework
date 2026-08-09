<?php
/**
 * Accessible CPD configuration, allocation and history interface.
 *
 * @category  Chisimba
 * @package   cpd
 * @author    Derek Keats
 * @copyright 2026 Derek Keats
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GNU GPL version 2
 */
$e = static function ($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); };
$action = function ($name) { return html_entity_decode($this->uri(array('action' => $name), 'cpd'), ENT_QUOTES, 'UTF-8'); };
$displayDate = static function ($value) {
    $value = (string) $value;
    return preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $parts)
        ? $parts[3] . '-' . $parts[2] . '-' . $parts[1]
        : $value;
};
$categoryName = array();
$schemeName = array();
$categoryCount = 0;
foreach ($cpdSchemes as $scheme) {
    $schemeName[$scheme['id']] = $scheme['name'];
    foreach ($cpdCategories[$scheme['id']] as $category) {
        $categoryName[$category['id']] = $category['name'];
        $categoryCount++;
    }
}
?>
<style>
.cpd-shell{max-width:1120px;margin:0 auto;padding:1rem}.cpd-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(290px,1fr));gap:1rem}.cpd-card{background:#fff;border:1px solid #d7dee5;border-radius:.75rem;padding:1rem}.cpd-card h2{margin-top:0}.cpd-field{display:block;margin:.75rem 0}.cpd-field input,.cpd-field select,.cpd-field textarea{display:block;width:100%;box-sizing:border-box;margin-top:.25rem;padding:.6rem;border:1px solid #8a98a8;border-radius:.35rem}.cpd-help{display:block;margin-top:.25rem;color:#536273;font-size:.92rem}.cpd-button{padding:.65rem 1rem;border:0;border-radius:.35rem;background:#175f84;color:#fff;font-weight:600}.cpd-result{padding:.75rem;border-radius:.35rem}.cpd-result.is-ok{background:#e5f5e9;color:#174f27}.cpd-result.is-error{background:#fde8e8;color:#742020}.cpd-notice{padding:.8rem;border-left:4px solid #b06b00;background:#fff7e6}.cpd-table-wrap{overflow-x:auto}.cpd-table{width:100%;border-collapse:collapse}.cpd-table th,.cpd-table td{padding:.6rem;border-bottom:1px solid #d7dee5;text-align:left}
</style>
<main class="cpd-shell">
  <h1><?php echo $e($cpdText['title']); ?></h1>
  <p><?php echo $e($cpdText['intro']); ?></p>
  <?php if (is_array($cpdResult)): ?>
    <p class="cpd-result <?php echo !empty($cpdResult['ok']) ? 'is-ok' : 'is-error'; ?>" role="status"><?php echo $e(!empty($cpdResult['ok']) ? $cpdText['result_success'] : ($cpdResult['message'] ?? $cpdText['result_failure'])); ?></p>
  <?php endif; ?>

  <?php if ($cpdIsAdmin && $cpdContextCode === ''): ?>
    <h2><?php echo $e($cpdText['schemeheading']); ?></h2>
    <div class="cpd-grid">
      <section class="cpd-card">
        <h2><?php echo $e($cpdText['createscheme']); ?></h2>
        <form method="post" action="<?php echo $e($action('createscheme')); ?>">
          <input type="hidden" name="csrf_token" value="<?php echo $e($cpdCsrf); ?>">
          <label class="cpd-field"><?php echo $e($cpdText['scheme_key']); ?><input name="scheme_key" required maxlength="100" pattern="[A-Za-z0-9][A-Za-z0-9_-]+" aria-describedby="cpd-scheme-key-help"><small class="cpd-help" id="cpd-scheme-key-help"><?php echo $e($cpdText['scheme_key_help']); ?></small></label>
          <label class="cpd-field"><?php echo $e($cpdText['scheme_name']); ?><input name="name" required maxlength="255"></label>
          <label class="cpd-field"><?php echo $e($cpdText['description']); ?><textarea name="description" rows="4"></textarea></label>
          <button class="cpd-button" type="submit"><?php echo $e($cpdText['create_scheme_button']); ?></button>
        </form>
      </section>
      <section class="cpd-card">
        <h2><?php echo $e($cpdText['createcategory']); ?></h2>
        <p><?php echo $e($cpdText['category_intro']); ?></p>
        <?php if (!$cpdSchemes): ?><p class="cpd-notice"><?php echo $e($cpdText['noschemes']); ?></p><?php else: ?>
        <form method="post" action="<?php echo $e($action('createcategory')); ?>">
          <input type="hidden" name="csrf_token" value="<?php echo $e($cpdCsrf); ?>">
          <label class="cpd-field"><?php echo $e($cpdText['scheme']); ?><select name="scheme_id" required><option value=""><?php echo $e($cpdText['select']); ?></option><?php foreach ($cpdSchemes as $scheme): ?><option value="<?php echo $e($scheme['id']); ?>"><?php echo $e($scheme['name']); ?></option><?php endforeach; ?></select></label>
          <label class="cpd-field"><?php echo $e($cpdText['category_key']); ?><input name="category_key" required maxlength="100" pattern="[A-Za-z0-9][A-Za-z0-9_-]+" aria-describedby="cpd-site-category-key-help"><small class="cpd-help" id="cpd-site-category-key-help"><?php echo $e($cpdText['category_key_help']); ?></small></label>
          <label class="cpd-field"><?php echo $e($cpdText['category_name']); ?><input name="name" required maxlength="255"></label>
          <label class="cpd-field"><?php echo $e($cpdText['description']); ?><textarea name="description" rows="4"></textarea></label>
          <button class="cpd-button" type="submit"><?php echo $e($cpdText['create_category_button']); ?></button>
        </form>
        <?php endif; ?>
      </section>
    </div>
  <?php endif; ?>

  <?php if ($cpdContextCode !== ''): ?>
    <h2><?php echo $e($cpdText['contextheading']); ?>: <?php echo $e($cpdContextTitle); ?></h2>
    <?php if ($cpdCanManage): ?>
    <div class="cpd-grid">
      <?php if ($cpdIsAdmin): ?>
      <section class="cpd-card">
        <h2><?php echo $e($cpdText['createcategory']); ?></h2>
        <p><?php echo $e($cpdText['category_intro']); ?></p>
        <?php if (!$cpdSchemes): ?><p class="cpd-notice"><?php echo $e($cpdText['noschemes']); ?></p><?php else: ?>
        <form method="post" action="<?php echo $e($action('createcategory')); ?>">
          <input type="hidden" name="csrf_token" value="<?php echo $e($cpdCsrf); ?>">
          <label class="cpd-field"><?php echo $e($cpdText['scheme']); ?><select name="scheme_id" required><?php foreach ($cpdSchemes as $scheme): ?><option value="<?php echo $e($scheme['id']); ?>"><?php echo $e($scheme['name']); ?></option><?php endforeach; ?></select></label>
          <label class="cpd-field"><?php echo $e($cpdText['category_key']); ?><input name="category_key" required maxlength="100" pattern="[A-Za-z0-9][A-Za-z0-9_-]+" aria-describedby="cpd-category-key-help"><small class="cpd-help" id="cpd-category-key-help"><?php echo $e($cpdText['category_key_help']); ?></small></label>
          <label class="cpd-field"><?php echo $e($cpdText['category_name']); ?><input name="name" required maxlength="255"></label>
          <label class="cpd-field"><?php echo $e($cpdText['description']); ?><textarea name="description" rows="3"></textarea></label>
          <button class="cpd-button" type="submit"><?php echo $e($cpdText['create_category_button']); ?></button>
        </form>
        <?php endif; ?>
      </section>
      <section class="cpd-card">
        <h2><?php echo $e($cpdText['recogniseheading']); ?></h2>
        <p><?php echo $e($cpdText['recognise_intro']); ?></p>
        <?php if (!$cpdSchemes): ?><p class="cpd-notice"><?php echo $e($cpdText['noschemes']); ?></p><?php elseif ($categoryCount === 0): ?><p class="cpd-notice"><?php echo $e($cpdText['nocategories']); ?></p><?php else: ?>
        <form method="post" action="<?php echo $e($action('recognise')); ?>">
          <input type="hidden" name="csrf_token" value="<?php echo $e($cpdCsrf); ?>">
          <label class="cpd-field"><?php echo $e($cpdText['scheme']); ?><select name="scheme_id" id="cpd-recognition-scheme" required><?php foreach ($cpdSchemes as $scheme): ?><?php if ($cpdCategories[$scheme['id']]): ?><option value="<?php echo $e($scheme['id']); ?>"><?php echo $e($scheme['name']); ?></option><?php endif; ?><?php endforeach; ?></select></label>
          <label class="cpd-field"><?php echo $e($cpdText['category']); ?><select name="category_id" id="cpd-recognition-category" required><?php foreach ($cpdCategories as $schemeId => $categories): ?><?php foreach ($categories as $category): ?><option value="<?php echo $e($category['id']); ?>" data-scheme-id="<?php echo $e($schemeId); ?>"><?php echo $e($category['name']); ?></option><?php endforeach; ?><?php endforeach; ?></select></label>
          <label class="cpd-field"><?php echo $e($cpdText['points']); ?><input name="points" type="number" min="0" max="1000000" step="0.001" required aria-describedby="cpd-points-help"><small class="cpd-help" id="cpd-points-help"><?php echo $e($cpdText['points_help']); ?></small></label>
          <label class="cpd-field"><?php echo $e($cpdText['validfrom']); ?><input name="valid_from" type="text" inputmode="numeric" placeholder="DD-MM-YYYY" pattern="[0-9]{2}-[0-9]{2}-[0-9]{4}" aria-describedby="cpd-date-help"></label>
          <label class="cpd-field"><?php echo $e($cpdText['validuntil']); ?><input name="valid_until" type="text" inputmode="numeric" placeholder="DD-MM-YYYY" pattern="[0-9]{2}-[0-9]{2}-[0-9]{4}" aria-describedby="cpd-date-help"><small class="cpd-help" id="cpd-date-help"><?php echo $e($cpdText['date_help']); ?></small></label>
          <label class="cpd-field"><?php echo $e($cpdText['reason']); ?><textarea name="reason" rows="3" required></textarea></label>
          <button class="cpd-button" type="submit"><?php echo $e($cpdText['recognise_button']); ?></button>
        </form><?php endif; ?>
      </section>
      <?php endif; ?>
      <section class="cpd-card">
        <h2><?php echo $e($cpdText['currentrecognition']); ?></h2>
        <?php if (!is_array($cpdRecognition)): ?><p><?php echo $e($cpdText['norecognition']); ?></p><?php else: ?>
          <dl><dt><?php echo $e($cpdText['scheme']); ?></dt><dd><?php echo $e($schemeName[$cpdRecognition['scheme_id']] ?? $cpdRecognition['scheme_id']); ?></dd><dt><?php echo $e($cpdText['category']); ?></dt><dd><?php echo $e($categoryName[$cpdRecognition['category_id']] ?? $cpdRecognition['category_id']); ?></dd><dt><?php echo $e($cpdText['points']); ?></dt><dd><?php echo $e($cpdRecognition['points']); ?></dd></dl>
        <?php endif; ?>
      </section>
      <?php if (is_array($cpdRecognition)): ?>
      <section class="cpd-card">
        <h2><?php echo $e($cpdText['allocateheading']); ?></h2>
        <form method="post" action="<?php echo $e($action('allocate')); ?>">
          <input type="hidden" name="csrf_token" value="<?php echo $e($cpdCsrf); ?>"><input type="hidden" name="scheme_id" value="<?php echo $e($cpdRecognition['scheme_id']); ?>"><input type="hidden" name="category_id" value="<?php echo $e($cpdRecognition['category_id']); ?>">
          <label class="cpd-field"><?php echo $e($cpdText['learner']); ?><select name="identity_user_id" required><option value=""><?php echo $e($cpdText['select']); ?></option><?php foreach ($cpdUsers as $user): ?><option value="<?php echo $e($user['userid'] ?? ''); ?>"><?php echo $e(trim(($user['firstname'] ?? '') . ' ' . ($user['surname'] ?? '')) . ' (' . ($user['username'] ?? '') . ')'); ?></option><?php endforeach; ?></select></label>
          <label class="cpd-field"><?php echo $e($cpdText['points']); ?><input name="points" type="number" min="0.001" max="1000000" step="0.001" value="<?php echo $e($cpdRecognition['points']); ?>" required></label>
          <label class="cpd-field"><?php echo $e($cpdText['completionbasis']); ?><textarea name="completion_basis" rows="3" required></textarea></label>
          <label class="cpd-field"><?php echo $e($cpdText['reason']); ?><textarea name="reason" rows="3" required></textarea></label>
          <label class="cpd-field"><?php echo $e($cpdText['effectivedate']); ?><input name="effective_date" type="text" inputmode="numeric" placeholder="DD-MM-YYYY" pattern="[0-9]{2}-[0-9]{2}-[0-9]{4}" value="<?php echo $e($cpdToday); ?>" required></label>
          <button class="cpd-button" type="submit"><?php echo $e($cpdText['allocate_button']); ?></button>
        </form>
      </section>
      <?php endif; ?>
    </div>
    <?php else: ?><p><?php echo $e($cpdText['ownhistory']); ?></p><?php endif; ?>
  <?php endif; ?>

  <section class="cpd-card">
    <h2><?php echo $e($cpdText['historyheading']); ?></h2>
    <?php if (!$cpdHistory): ?><p><?php echo $e($cpdText['nohistory']); ?></p><?php else: ?>
    <div class="cpd-table-wrap"><table class="cpd-table"><thead><tr><th scope="col"><?php echo $e($cpdText['date']); ?></th><th scope="col"><?php echo $e($cpdText['identity']); ?></th><th scope="col"><?php echo $e($cpdText['type']); ?></th><th scope="col"><?php echo $e($cpdText['points']); ?></th><th scope="col"><?php echo $e($cpdText['reason']); ?></th></tr></thead><tbody>
    <?php foreach ($cpdHistory as $row): ?><tr><td><?php echo $e($displayDate($row['effective_date'] ?? '')); ?></td><td><?php echo $e($row['identity_user_id'] ?? ''); ?></td><td><?php echo $e($row['transaction_type'] ?? ''); ?></td><td><?php echo $e($row['points_delta'] ?? ''); ?></td><td><?php echo $e($row['reason'] ?? ''); ?></td></tr><?php endforeach; ?>
    </tbody></table></div><?php endif; ?>
  </section>
</main>
<script>
(function () {
  'use strict';
  var scheme = document.getElementById('cpd-recognition-scheme');
  var category = document.getElementById('cpd-recognition-category');
  if (!scheme || !category) { return; }
  var options = Array.prototype.slice.call(category.options);
  function filterCategories() {
    var first = null;
    options.forEach(function (option) {
      var visible = option.getAttribute('data-scheme-id') === scheme.value;
      option.hidden = !visible;
      option.disabled = !visible;
      if (visible && first === null) { first = option; }
    });
    if (first !== null) { first.selected = true; }
  }
  scheme.addEventListener('change', filterCategories);
  filterCategories();
}());
</script>
