<?php get_header(); ?>
<div id="account-page-ui">
	<div class="account-menu-header">

	</div>
	<div class="wizard-tabs" id="account-menu-tabs">
		<ul class="wizard-tabs-list">
			<li data-tab="tab-1" class="active"><i class="fa-solid fa-user"></i><span class="tab-item-label">Your
					Info</span><span class="tab-item-indicator"><i class="fa-solid fa-chevron-right"></i></span></li>
			<li data-tab="tab-2"><i class="fa-solid fa-sliders"></i><span class="tab-item-label">Manage Plan</span><span
					class="tab-item-indicator"><i class="fa-solid fa-chevron-right"></i></span></li>
			<li data-tab="tab-3"><i class="fa-solid fa-credit-card"></i><span class="tab-item-label">Billing
					Settings</span><span class="tab-item-indicator"><i class="fa-solid fa-chevron-right"></i></span>
			</li>
		</ul>
		<div class="wizard-tab-content active" data-content="tab-1">
			Content for Tab 1
		</div>
		<div class="wizard-tab-content" data-content="tab-2">
			Content for Tab 2
		</div>
		<div class="wizard-tab-content" data-content="tab-3">
			Content for Tab 3
		</div>
	</div>
</div>
<?php echo get_footer(); ?>