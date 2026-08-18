<?php
script('workplanner', 'quick');
style('workplanner', 'workplanner');
?>
<div id="workplanner-quick" class="workplanner-quick">
	<header class="workplanner-quick__header">
		<div>
			<h2><?php p($l->t('Quick entry')); ?></h2>
			<p><?php p($l->t('Create a work location entry from your mobile device.')); ?></p>
		</div>
		<a class="button" href="../workplanner/"><?php p($l->t('Overview')); ?></a>
	</header>

	<form class="workplanner-quick__form">
		<div class="workplanner__status" data-role="status"></div>
		<label>
			<span><?php p($l->t('Date')); ?></span>
			<input type="date" name="day" required>
		</label>
		<label>
			<span><?php p($l->t('Location')); ?></span>
			<select name="locationId" required></select>
		</label>
		<div class="workplanner-quick__times">
			<label>
				<span><?php p($l->t('From')); ?></span>
				<input type="time" name="timeFrom">
			</label>
			<label>
				<span><?php p($l->t('To')); ?></span>
				<input type="time" name="timeTo">
			</label>
		</div>
		<label>
			<span><?php p($l->t('Note')); ?></span>
			<textarea name="note" maxlength="1000" rows="5"></textarea>
		</label>
		<button type="submit" class="button primary workplanner-quick__save"><?php p($l->t('Save')); ?></button>
	</form>
</div>
