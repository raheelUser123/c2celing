<?php $mode=$mode??'consult'; $selected=$selected??''; ?>
<form class="c2c-lead-form multi-step-wizard" action="<?=e(url('handlers/submit-lead.php'))?>" method="post" enctype="multipart/form-data" data-ajax-form data-multi-step-form novalidate>
  <input type="hidden" name="mode" value="<?=e($mode)?>">
  <input type="hidden" name="page_url" value="<?=e((isset($_SERVER['HTTPS'])?'https':'http').'://'.($_SERVER['HTTP_HOST']??'localhost').($_SERVER['REQUEST_URI']??'/'))?>">
  <div class="hp-field" aria-hidden="true"><label>Company website<input name="company_website" tabindex="-1" autocomplete="off"></label></div>

  <!-- Calendly-style Header & Stepper Progress Bar -->
  <div class="form-wizard-header">
    <div class="form-heading">
      <span><?=icon($mode==='estimate'?'camera':'calendar')?></span>
      <div>
        <p class="form-kicker"><?=$mode==='estimate'?'PROJECT REVIEW':'15-MINUTE INTRO CALL'?></p>
        <h3><?=$mode==='estimate'?'Request an Estimate':'Book Your Consultation'?></h3>
      </div>
    </div>
    
    <div class="stepper-bar">
      <div class="stepper-meta">
        <span class="step-label-text" data-step-label>Step 1 of 3: Project Scope</span>
        <span class="step-percentage" data-step-percent>33%</span>
      </div>
      <div class="stepper-track">
        <div class="stepper-fill" data-step-fill style="width: 33%;"></div>
      </div>
      <div class="stepper-nodes">
        <div class="step-node active" data-node="1"><span>1</span><small>Scope</small></div>
        <div class="step-node" data-node="2"><span>2</span><small>Contact</small></div>
        <div class="step-node" data-node="3"><span>3</span><small>Details</small></div>
      </div>
    </div>
  </div>

  <div class="form-message" aria-live="polite"></div>

  <!-- STEP 1: Project Scope & Budget -->
  <div class="step-panel active" data-step="1">
    <div class="step-panel-title">
      <h4>What kind of project are you planning?</h4>
      <p>Select your room and estimated timeline so we can prepare our team.</p>
    </div>
    <label>Project type <span aria-hidden="true">*</span>
      <select name="project_type" required>
        <option value="">Choose a project</option>
        <?php foreach(['Basement','Kitchen','Bathroom','Full-room / Multi-room'] as $v):?>
          <option <?=stripos($v,$selected)!==false?'selected':''?>><?=e($v)?></option>
        <?php endforeach;?>
      </select>
    </label>
    <div class="field-grid">
      <label>Target start
        <select name="start_window">
          <option>ASAP</option>
          <option>1-2 months</option>
          <option>3-6 months</option>
          <option>Planning 6+ months</option>
        </select>
      </label>
      <label>Budget range
        <select name="budget">
          <option>Not sure</option>
          <option>Under $15,000</option>
          <option>$15,000-$30,000</option>
          <option>$30,000-$75,000</option>
          <option>$75,000+</option>
        </select>
      </label>
    </div>
    <div class="step-actions">
      <button type="button" class="btn btn-primary step-btn-next" data-next-step>Continue to Contact Info <?=icon('arrow')?></button>
    </div>
  </div>

  <!-- STEP 2: Contact Information & Location -->
  <div class="step-panel" data-step="2">
    <div class="step-panel-title">
      <h4>How can we get in touch?</h4>
      <p>We will use these details to coordinate your consultation or review.</p>
    </div>
    <div class="field-grid">
      <label>Full name <span aria-hidden="true">*</span><input name="full_name" autocomplete="name" required></label>
      <label>Email <span aria-hidden="true">*</span><input type="email" name="email" autocomplete="email" required></label>
    </div>
    <div class="field-grid">
      <label>Phone <span aria-hidden="true">*</span><input type="tel" name="phone" autocomplete="tel" placeholder="(716) 555-0123" required></label>
      <label>City or ZIP <span aria-hidden="true">*</span><input name="city_zip" autocomplete="postal-code" required></label>
    </div>
    <label>Are you a decision maker?
      <select name="decision_maker">
        <option>Yes</option>
        <option>No</option>
        <option>Not sure</option>
      </select>
    </label>
    <?php if($mode==='estimate'):?>
      <label>Project address<input name="address" autocomplete="street-address" placeholder="123 Main St"></label>
    <?php endif;?>
    <div class="step-actions">
      <button type="button" class="btn btn-secondary step-btn-prev" data-prev-step><?=icon('arrow')?> Back</button>
      <button type="button" class="btn btn-primary step-btn-next" data-next-step>Continue to Final Details <?=icon('arrow')?></button>
    </div>
  </div>

  <!-- STEP 3: Photos & Project Details -->
  <div class="step-panel" data-step="3">
    <div class="step-panel-title">
      <h4>Final details & context</h4>
      <p>Provide photos or specific notes to give our team a complete picture.</p>
    </div>
    <?php if($mode==='estimate'):?>
      <label>Project photos <span class="optional">JPG, PNG or PDF</span>
        <input type="file" name="photos[]" accept=".jpg,.jpeg,.png,.pdf" multiple data-file-input>
        <small>Upload up to <?=MAX_UPLOAD_FILES?> files, <?=MAX_UPLOAD_MB?> MB each. 3-8 photos are ideal.</small>
        <span class="file-summary" data-file-summary></span>
      </label>
      <label>Project notes
        <textarea name="notes" rows="3" placeholder="Tell us what you want to change, known concerns, and desired outcome."></textarea>
      </label>
    <?php else:?>
      <label>What would make this consultation useful?
        <textarea name="notes" rows="3" placeholder="Share the room, your priorities, and any specific questions."></textarea>
      </label>
    <?php endif;?>
    <label>How did you first hear about us?
      <select name="source">
        <option>Google</option>
        <option>Referral</option>
        <option>Facebook</option>
        <option>Drove by</option>
        <option>Other</option>
      </select>
    </label>
    <div class="step-actions">
      <button type="button" class="btn btn-secondary step-btn-prev" data-prev-step><?=icon('arrow')?> Back</button>
      <button class="btn btn-primary step-btn-submit" type="submit"><?=$mode==='estimate'?'Get My Estimate Review':'Book My Consult'?></button>
    </div>
  </div>

  <p class="form-microcopy">Your information is sent securely to our team and used only to coordinate your project.</p>
</form>
