<?php
script('workplanner', 'admin');
style('workplanner', 'workplanner');
?>
<div id="workplanner-admin" class="section workplanner-admin">
	<h2><?php p($l->t('Workplanner locations')); ?></h2>
	<p><?php p($l->t('Create the locations users can select for their work planning.')); ?></p>

	<form class="workplanner-admin__form" data-role="location-form">
		<input type="hidden" name="id">
		<label>
			<span><?php p($l->t('Name')); ?></span>
			<input type="text" name="name" maxlength="120" required>
		</label>
		<label>
			<span><?php p($l->t('Color')); ?></span>
			<input type="color" name="color" value="#2f6fdd">
		</label>
		<label>
			<span><?php p($l->t('Sort order')); ?></span>
			<input type="number" name="sortOrder" value="0">
		</label>
		<label>
			<span><?php p($l->t('Description')); ?></span>
			<input type="text" name="description" maxlength="255">
		</label>
		<label class="workplanner-admin__check">
			<input type="checkbox" name="active" checked>
			<span><?php p($l->t('Active')); ?></span>
		</label>
		<div class="workplanner-admin__actions">
			<button type="button" class="button" data-action="reset"><?php p($l->t('New')); ?></button>
			<button type="submit" class="button primary"><?php p($l->t('Save location')); ?></button>
		</div>
	</form>

	<div class="workplanner__status" data-role="status"></div>
	<table class="workplanner-admin__table">
		<thead>
			<tr>
				<th><?php p($l->t('Location')); ?></th>
				<th><?php p($l->t('Description')); ?></th>
				<th><?php p($l->t('Status')); ?></th>
				<th><?php p($l->t('Actions')); ?></th>
			</tr>
		</thead>
		<tbody data-role="locations"></tbody>
	</table>
</div>
