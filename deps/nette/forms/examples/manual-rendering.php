<?php

/**
 * Nette Forms manual form rendering.
 */
declare (strict_types=1);
namespace Packetery;

if (@(!(include __DIR__ . '/../vendor/autoload.php'))) {
    die('Install packages using `composer install`');
}
use Packetery\Nette\Forms\Form;
use Packetery\Tracy\Debugger;
use Packetery\Tracy\Dumper;
Debugger::enable();
$form = new Form();
$form->addText('name')->setRequired('Enter your name');
$form->addText('age')->setRequired('Enter your age');
$form->addRadioList('gender', null, ['m' => 'male', 'f' => 'female']);
$form->addEmail('email');
$form->addSubmit('submit');
if ($form->isSuccess()) {
    echo '<h2>Form was submitted and successfully validated</h2>';
    Dumper::dump($form->getValues());
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Nette Forms manual form rendering</title>
	<link rel="stylesheet" media="screen" href="assets/style.css" />
	<script src="https://nette.github.io/resources/js/3/netteForms.js"></script>
</head>

<body>
	<h1>Nette Forms manual form rendering</h1>

	<?php 
$form->render('begin');
?>

	<?php 
if ($form->errors) {
    ?>
	<ul class="error">
		<?php 
    foreach ($form->errors as $error) {
        ?>
		<li><?php 
        echo \htmlspecialchars($error);
        ?></li>
		<?php 
    }
    ?>
	</ul>
	<?php 
}
?>

	<fieldset>
		<legend>Personal data</legend>
		<table>
		<tr class="required">
			<th><?php 
echo $form['name']->getLabel('Your name:');
?></th>
			<td><?php 
echo $form['name']->control->cols(35);
?> <?php 
echo $form['name']->error;
?></td>
		</tr>
		<tr class="required">
			<th><?php 
echo $form['age']->getLabel('Your age:');
?></th>
			<td><?php 
echo $form['age']->control->cols(5);
?> <?php 
echo $form['age']->error;
?></td>
		</tr>
		<tr>
			<th><?php 
echo $form['gender']->getLabel('Your gender:');
?></th>
			<td><?php 
echo $form['gender']->control;
?> <?php 
echo $form['gender']->error;
?></td>
		</tr>
		<tr>
			<th><?php 
echo $form['email']->getLabel('Email:');
?></th>
			<td><?php 
echo $form['email']->control->cols(35);
?> <?php 
echo $form['email']->error;
?></td>
		</tr>
		</table>
	</fieldset>

	<div>
		<?php 
echo $form['submit']->getControl('Send');
?>
	</div>

	<?php 
$form->render('end');
?>
</body>
</html>
<?php 
