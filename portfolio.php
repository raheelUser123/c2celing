<?php
$pageTitle = 'Our Work';
$pageDescription = 'Browse recent remodeling projects from ceiling to cellar.';
require 'includes/header.php';
?>
<section class="page-hero simple"><div class="container narrow reveal"><span class="eyebrow dark">OUR WORK</span><h1>Recent remodeling projects.</h1><p>A look at the finished spaces we plan and build — clear, consistent, and certain.</p></div></section>
<section class="section"><div class="container">
<div class="portfolio-grid">
<?php
$projects = [
    'assets/images/projects/project1.jpeg',
    'assets/images/projects/project2.jpeg',
    'assets/images/projects/project3.jpeg',
    'assets/images/projects/project4.jpeg',
    'assets/images/projects/project5.jpeg',
    'assets/images/projects/project6.jpeg',
    'assets/images/projects/project7.jpeg',
    'assets/images/projects/project8.jpeg',
];
foreach ($projects as $i => $src):
?>
<figure class="portfolio-item"><img src="<?=e(url($src))?>" alt="Remodeling project photo <?=e((string)($i + 1))?>" loading="lazy"></figure>
<?php endforeach; ?>
</div>
</div></section>
<?php require 'includes/footer.php'; ?>
