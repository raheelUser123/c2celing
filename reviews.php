<?php 
$pageTitle = 'Client Reviews'; 
$pageDescription = 'Real feedback from businesses and homeowners we support across Western New York.'; 
require 'includes/header.php'; 
?>

<section class="page-hero simple">
  <div class="container narrow">
    <span class="eyebrow dark">CLIENT REVIEWS</span>
    <h1>Real homeowner & business feedback.</h1>
    <p>We do not publish fabricated testimonials or ratings. Browse verified experiences from homeowners and clients we have supported across Western New York.</p>
  </div>
</section>

<?php require __DIR__ . '/includes/reviews-carousel.php'; ?>

<section class="section bg-mist">
  <div class="container">
    <div class="centered section-heading">
      <span class="eyebrow dark">OUR VERIFICATION STANDARDS</span>
      <h2>Useful feedback with enough context to be credible.</h2>
    </div>
    <div class="grid-3 fit-grid">
      <article>
        <h3>Project context</h3>
        <p>The room, scope, and specific remodeling goals connected to each review.</p>
      </article>
      <article>
        <h3>Verifiable source</h3>
        <p>Direct customer authorization supporting publication without fabricated scores.</p>
      </article>
      <article>
        <h3>Plain language</h3>
        <p>The customer's actual experience without invented marketing performance claims.</p>
      </article>
    </div>
  </div>
</section>

<?php require 'includes/footer.php'; ?>



