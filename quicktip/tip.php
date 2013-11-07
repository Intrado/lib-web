<?
	// temp stubbed API response for org tip data	
	$json_stub = json_encode(array(
		"orgname" => "Example School District",
		"categories" => array(
			"1" => "General",
			"2" => "Bullying",
	        "3" => "Drugs",
	        "4" => "Fighting",
	        "5" => "Personal Crisis",
	        "6" => "Threat",
	        "7" => "Truancy",
	        "8" => "Vandalism",
	        "9" => "Weapons"
		),
		"organizations" => array(
			"1" => "Santa Cruz County School District",
			"2" => "Aptos High",
	        "3" => "Harbor High",
	        "4" => "Monte Vista High",
	        "5" => "Santa Cruz High",
	        "6" => "Scotts Valley High",
	        "7" => "Soquel High",
	        "8" => "Watsonville High"
		)
	));

	$response = json_decode($json_stub);

	// required fields
	$categoryId = isset($_POST['tip-category-id']) ? $_POST['tip-category-id'] : null;
	$orgId 	 	= isset($_POST['tip-org-id']) ? $_POST['tip-org-id'] : null;
	$tipMessage = isset($_POST['tip-message']) ? $_POST['tip-message'] : null;

	// optional attachment
	$attachment = isset($_FILES['tip-file-attachment']['name']) ? $_FILES['tip-file-attachment']['name'] : null;

	// user-provided contact info, if any
	$firstname   = isset($_POST['firstname']) ? $_POST['firstname'] : null;
	$lastname   = isset($_POST['lastname']) ? $_POST['lastname'] : null;
	$email 	= isset($_POST['email'])  ? $_POST['email']  : null;
	$phone  = isset($_POST['phone'])  ? $_POST['phone']  : null;

	// if user has submitted a tip, get the category name and org name to show in summary
	// TODO: replace depending on final API (in progress)
	$categoryName = $categoryId ? $response->categories->$categoryId : "";
	$orgName = $orgId ? $response->organizations->$orgId : "";
?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<title>Quick Tip - Submit an Annoymous Tip - Powered by SchoolMessenger</title>
		<link rel="stylesheet" type="text/css" href="tip.css">
	</head>
	<body>
		<div id="tip-container">
			<div class="tip-chat"></div>
			<h1>SchoolMessenger Quick Tip</h1>
			<div id="tip-orgname-label"><?= $response->orgname ?></div>

			<? if ($tipMessage) { ?>

					<div id="thank-you" class="alert">
						<h1>Thank You for the Tip!</h3>
						<div class="text-danger call911">If this is an emergency, please call 911.</div>
					</div>
					<div class="summary-info">
						<div class="summary-heading">Summary of the tip information you submitted:</div>
						<div><span class="summary-label">Organization:</span> &nbsp;<div class="summary-value"><?= $orgName ?></div></div>
						<div><span class="summary-label">Category:</span> &nbsp;<div class="summary-value"><?= $categoryName ?></div></div>
						<div><span class="summary-label">Message:</span> &nbsp;<div class="summary-value message-text">"<?= $tipMessage ?>"</div></div>
						<? if ($attachment) {
								echo '<div id="summary-attachment-container"><span class="summary-label">Attachment:</span> &nbsp;<div class="summary-value">'.$attachment.'</div></div>';
							}
						?>

						<? if ($firstname != null || $lastname != null || $email != null || $phone != null) {
								echo '<div class="alert contact-info">
										<div class="summary-heading">Contact info you provided with your tip:</div>';
								if ($firstname != null && $lastname != null) {
									echo '<div><span class="summary-label">Name:</span> &nbsp;<div class="summary-value">'.$firstname.' '.$lastname.'</div></div>';
								}
								if ($firstname != null && $lastname == null) {
									echo '<div><span class="summary-label">First Name:</span> &nbsp;<div class="summary-value">'.$firstname.'</div></div>';
								}
								if ($firstname == null && $lastname != null) {
									echo '<div><span class="summary-label">Last Name:</span> &nbsp;<div class="summary-value">'.$lastname.'</div></div>';
								}								
								if ($email != null) {
									echo '<div><span class="summary-label">Email:</span> &nbsp;<div class="summary-value">'.$email.'</div></div>';
								}
								if ($phone != null) {
									echo '<div><span class="summary-label">Phone:</span> &nbsp;<div class="summary-value">'.$phone.'</div></div>';
								}
								echo '</div>';
							}
						?>
					</div>
					<form id="newquicktip" name="newquicktip" action="<? echo $_SERVER['PHP_SELF']; ?>" method="POST">
						<fieldset>
							<button id="new-tip" class="btn btn-lg btn-danger" type="submit">Done</button>
						</fieldset>
					</form>
			<? } else { ?>

			<div class="alert"><strong>SchoolMessenger Quick Tip allows you to submit an anonymous tip to school and district officials.</strong>
				Please select the appropriate organization and category when submitting your tip.
				<div class="text-danger call911">If this is an emergency, please call 911.</div>
			</div>
			<form id="quicktip" name="quicktip" action="<? $PHP_SELF ?>" method="POST" enctype="multipart/form-data">
				<fieldset>
					<label for="tip-org-id">Organization <span class="sup" title="Required field">*</span></label>
					<select id="tip-org-id" name="tip-org-id">
						<?
							foreach ($response->organizations as $id => $name) {
								echo '<option value="'.$id.'">'.$name.'</option>';
							}
						?>
					</select>
					<label for="tip-category-id">Tip Category <span class="sup" title="Required field">*</span></label>
					<select id="tip-category-id" name="tip-category-id">
						<?
							foreach ($response->categories as $id => $name) {
								echo '<option value="'.$id.'">'.$name.'</option>';
							}
						?>
					</select>

					<div id="tip-message-control-group" class="form-group">
						<label for="tip-message" class="control-label">Tip Message <span class="sup" title="Required field">*</span></label>
						<textarea id="tip-message" class="form-control" name="tip-message" rows="8" placeholder="Enter your tip here..." ></textarea>
					</div>

				</fieldset>
				<fieldset>
					<label for="tip-file-attachment" title="Optional">Do you have a related image?</label>
					<div id="tip-attach-instruction">If so, you can attach it to your tip to help provide additional information.</div>
					<input id="tip-file-attachment" name="tip-file-attachment" type="file">
 				</fieldset>
				<div id="tip-contact" class="alert">
					<h4>Contact Information &nbsp;<span class="small">(Optional)</span></h4>
					<p>You have the option to leave your personal contact information. If provided, you may be contacted for more information if necessary.</p>
					<fieldset>
						<label for="firstname">First</label>
						<input id="firstname" name="firstname" type="text" placeholder="First name" value="" title="Enter your first name">
						<label for="lastname">Last</label>
						<input id="lastname" name="lastname" type="text" placeholder="Last name" value="" title="Enter your last name">
					</fieldset>
					<fieldset>
						<label for="email">Email</label>
						<input id="email" name="email" type="email" placeholder="Email address" value="" title="Enter your email address, ex. janedoe@example.com">
					</fieldset>
					<fieldset>
						<label for="phone">Phone</label>
						<input id="phone" name="phone" type="tel" pattern='\d{3}-\d{3}-\d{4}' placeholder="Phone number" value="" title="Enter your phone number, ex. 555-123-4567">
					</fieldset>
				</div>
				<div id="tip-error-message" class="alert alert-danger hide">Please enter a Tip Message.</div>
				<fieldset>
					<button id="tip-submit" class="btn btn-lg btn-danger" type="submit">Submit Tip</button>
				</fieldset>
			</form>
<? } ?>

		</div>

		<?	// only init QuickTip if we're on the starting page (not the Thank You landing page)
			if ($tipMessage == null) { ?>
				<script type="text/javascript" src="tip.js"></script>
				<script type="text/javascript">
					window.onload = function() {
						new QuickTip();
					};
				</script>
		<? } ?>	
	</body>
</html>
