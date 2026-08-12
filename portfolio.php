<?php
$pageTitle = 'Our Work';
$pageDescription = 'Browse recent remodeling projects from ceiling to cellar.';
require 'includes/header.php';

/* ------------------------------------------------------------------
 * Portfolio is built automatically from the folders inside
 * assets/images/projects/. Each folder becomes a tab, and the images
 * inside that folder become the photo grid for that tab.
 * ---------------------------------------------------------------- */
$projectsRoot = __DIR__ . '/assets/images/projects';
$categories   = [];
if (is_dir($projectsRoot)) {
    foreach (glob($projectsRoot . '/*', GLOB_ONLYDIR) as $dir) {
        $folderName = basename($dir);
        $images     = [];
        foreach (glob($dir . '/*') as $file) {
            if (in_array(strtolower((string) pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $images[] = basename($file);
            }
        }
        if ($images !== []) {
            $categories[$folderName] = $images;
        }
    }
}
ksort($categories);
$totalPhotos = array_sum(array_map('count', $categories));

$slugify = static function (string $name): string {
    return 'panel-' . strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $name), '-'));
};

$renderCard = static function (string $folderName, string $fileName): void {
    $rel   = 'assets/images/projects/' . rawurlencode($folderName) . '/' . rawurlencode($fileName);
    $lower = strtolower($fileName);
    $badge = str_contains($lower, 'before') ? '<span class="portfolio-badge">Before</span>'
           : (str_contains($lower, 'after') ? '<span class="portfolio-badge">After</span>' : '');
    $label = (string) pathinfo($fileName, PATHINFO_FILENAME);
    echo '<figure class="portfolio-card">' . $badge
        . '<img src="' . e(url($rel)) . '" alt="' . e($folderName . ' — ' . $label) . '" loading="lazy" decoding="async" width="800" height="600">'
        . '<figcaption><b>' . e($folderName) . '</b><small>' . e($label) . '</small></figcaption>'
        . '</figure>';
};
?>
<section class="page-hero simple"><div class="container narrow reveal"><span class="eyebrow dark">OUR WORK</span><h1>Recent remodeling projects.</h1><p>A look at the finished spaces we plan and build — clear, consistent, and certain.<?php if ($totalPhotos > 0): ?><span class="hero-photo-count"><?= (string)$totalPhotos ?> project photos across <?= count($categories) ?> categories.</span><?php endif; ?></p></div></section>
<section class="section portfolio-section"><div class="container">
<?php if ($categories === []): ?>
  <p class="portfolio-empty">Project photos are being prepared — check back soon.</p>
<?php else: ?>
  <div class="portfolio-tabs" role="tablist" aria-label="Portfolio categories">
    <button class="portfolio-tab active" role="tab" id="tab-panel-all" aria-selected="true" aria-controls="panel-all" data-tab="panel-all">
      <?= icon('camera') ?><span>All Projects</span><span class="tab-count"><?= (string)$totalPhotos ?></span>
    </button>
    <?php foreach ($categories as $folderName => $images): $panelId = $slugify($folderName); ?>
    <button class="portfolio-tab" role="tab" id="tab-<?= e($panelId) ?>" aria-selected="false" aria-controls="<?= e($panelId) ?>" data-tab="<?= e($panelId) ?>">
      <?= icon('camera') ?><span><?= e($folderName) ?></span><span class="tab-count"><?= count($images) ?></span>
    </button>
    <?php endforeach; ?>
  </div>

  <div class="portfolio-panel" id="panel-all" role="tabpanel" aria-labelledby="tab-panel-all">
    <div class="portfolio-grid">
    <?php foreach ($categories as $folderName => $images): foreach ($images as $fileName): $renderCard($folderName, $fileName); endforeach; endforeach; ?>
    </div>
  </div>

  <?php foreach ($categories as $folderName => $images): $panelId = $slugify($folderName); ?>
  <div class="portfolio-panel" id="<?= e($panelId) ?>" role="tabpanel" aria-labelledby="tab-<?= e($panelId) ?>" hidden>
    <div class="portfolio-grid">
    <?php foreach ($images as $fileName): $renderCard($folderName, $fileName); endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>
<?php endif; ?>
</div></section>
<?php require 'includes/footer.php'; ?>
