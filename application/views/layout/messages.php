<!-- Display Success -->
<?php if($this->session->flashdata('success') != '') { ?>
<div class="alert alert-success" role="alert">
        <?php echo $this->session->flashdata('success'); ?>
</div>
<?php } ?>

<!-- Display Notices -->
<?php if($this->session->flashdata('notice') != '') { ?>
<div class="alert alert-info" role="alert">
        <?php echo $this->session->flashdata('notice'); ?>
</div>
<?php } ?>

<!-- Display Warnings -->
<?php if($this->session->flashdata('warning') != '') { ?>
<div class="alert alert-warning" role="alert">
        <?php echo $this->session->flashdata('warning'); ?>
</div>
<?php } ?>

<!-- Display Errors -->
<?php if($this->session->flashdata('error') != '') { ?>
<div class="alert alert-danger" role="alert">
        <?php echo $this->session->flashdata('error'); ?>
</div>
<?php } ?>

<!-- Display form validation errors -->
<?php if(validation_errors()) { ?>
<div class="alert alert-danger" role="alert">
	<a class="btn-close" data-bs-dismiss="alert">x</a>
	<?php echo validation_errors(); ?>
</div>
<?php } ?>

 <?php if ($this->session->flashdata('success0')) { ?>
	<!-- Display Success Message -->
	<div class="alert alert-success">
		<?php echo $this->session->flashdata('success0'); ?>
	</div>
<?php } ?>

<?php if ($this->session->flashdata('success1')) { ?>
	<!-- Display Success Message -->
	<div class="alert alert-success">
		<?php echo $this->session->flashdata('success1'); ?>
	</div>
<?php } ?>

<?php if ($this->session->flashdata('success2')) { ?>
	<!-- Display Success Message -->
	<div class="alert alert-success">
		<?php echo $this->session->flashdata('success2'); ?>
	</div>
<?php } ?>

<?php if ($this->session->flashdata('message')) { ?>
	<!-- Display Message -->
	<div class="alert-message error">
		<?php echo $this->session->flashdata('message'); ?>
	</div>
<?php } ?>

<?php if($this->session->flashdata('testmailFailed')) { ?>
	<!-- Display testmailFailed Message -->
	<div class="alert alert-danger">
		<?php echo $this->session->flashdata('testmailFailed'); ?>
	</div>
<?php } ?>

<?php if($this->session->flashdata('testmailSuccess')) { ?>
	<!-- Display testmailSuccess Message -->
	<div class="alert alert-success">
		<?php echo $this->session->flashdata('testmailSuccess'); ?>
	</div>
<?php } ?>
