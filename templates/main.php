<?php
script('workplanner', 'workplanner');
style('workplanner', 'workplanner');
?>
<div id="workplanner-app" class="workplanner">
	<header class="workplanner__toolbar">
		<div>
			<h2><?php p($l->t('Workplanner')); ?></h2>
			<p><?php p($l->t('Plan and view team work locations.')); ?></p>
		</div>
		<div class="workplanner__controls">
			<button type="button" class="button" data-action="previous"><?php p($l->t('Previous')); ?></button>
			<button type="button" class="button" data-action="today"><?php p($l->t('Today')); ?></button>
			<button type="button" class="button" data-action="next"><?php p($l->t('Next')); ?></button>
			<a class="button" href="#" data-role="quick-link"><?php p($l->t('Quick entry')); ?></a>
			<select data-role="view-mode" aria-label="<?php p($l->t('View')); ?>">
				<option value="week"><?php p($l->t('Week')); ?></option>
				<option value="month"><?php p($l->t('Month')); ?></option>
			</select>
		</div>
	</header>
	<div class="workplanner__status" data-role="status"></div>
	<section class="workplanner-feed" data-role="feed" hidden>
		<div>
			<strong><?php p($l->t('Calendar feed')); ?></strong>
			<p><?php p($l->t('Read-only calendar subscription with all planning entries.')); ?></p>
		</div>
		<div class="workplanner-feed__actions">
			<input type="text" readonly data-role="feed-url" aria-label="<?php p($l->t('Calendar feed URL')); ?>">
			<button type="button" class="button" data-action="copy-feed"><?php p($l->t('Copy link')); ?></button>
		</div>
	</section>
	<div class="workplanner__range-title" data-role="range-title"></div>
	<div class="workplanner__grid" data-role="calendar"></div>
</div>

<div id="workplanner-dialog" class="workplanner-dialog" hidden>
	<form class="workplanner-dialog__panel">
		<h3 data-role="dialog-title"></h3>
		<label>
			<span><?php p($l->t('Location')); ?></span>
			<select name="locationId" required></select>
		</label>
		<label>
			<span><?php p($l->t('From')); ?></span>
			<input type="time" name="timeFrom" autocomplete="off">
		</label>
		<label>
			<span><?php p($l->t('To')); ?></span>
			<input type="time" name="timeTo" autocomplete="off">
		</label>
		<label>
			<span><?php p($l->t('Note')); ?></span>
			<textarea name="note" maxlength="1000" rows="5" autocomplete="off"></textarea>
		</label>
		<div class="workplanner-dialog__actions">
			<button type="button" class="button" data-action="delete"><?php p($l->t('Remove')); ?></button>
			<button type="button" class="button" data-action="cancel"><?php p($l->t('Cancel')); ?></button>
			<button type="submit" class="button primary"><?php p($l->t('Save')); ?></button>
		</div>
	</form>
</div>
