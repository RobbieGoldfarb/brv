<?php
$this->setTitle('Brevada - Sign Up');
$this->addResource('/css/layout.css');
$this->addResource('/css/signup.css');
$this->addResource('/js/signup.js');

$this->addResource("<meta name='viewport' content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0'/>", true, true);

if(Brevada::IsLoggedIn()){
	Brevada::Redirect('/dashboard');
}

$level = 1;
if(isset($_GET['l'])){
	$level=strtolower($_GET['l']);
	if($level=="free"){
		$level=1;
	}
	else if($level=="personal"){
		$level=2;
	}
	else if($level=="professional"){
		$level=3;
	}
	else if($level=="enterprise"){
		$level=4;
	}
}

$existing_address = isset($_GET['email']) && $_GET['email'] != 'false';
?>

<div id="signup_box" style='display: none;'>
	<a href="/index.php" ><img src="/images/brevada.png" id='logo' style="margin:0 auto; width:150px; outline:none;" /></a>
	<form method="post" action="/overall/insert/insert_user.php">
		<div id='part1'>
			<div class="signup_instruction">What do you want to get feedback on?</div>
				<div class='token-container'>
					<div class='tokens'>
						<?php
						if(($query = Database::query("SELECT aspect_type.Title, aspect_type.ID as AspectTypeID FROM aspect_type ORDER BY aspect_type.Title ASC")) !== false){
							while($row = $query->fetch_assoc()){
								echo "<div class='token noselect' data-tokenid='{$row['AspectTypeID']}'><span>{$row['Title']}</span></div>";
							}
						}
						?>
						<input type='hidden' name='posts-token' id='tokens' />
					</div>
					<div id="next" class="submit-next">Next</div>
				</div>
				<br style="clear:both;" />
		</div>
		<div id='part2' style='display:none;'>
			<input class="in" type="email" name="email" <?php if($existing_address){ ?> placeholder="<?php echo $_GET['email']; ?>"<?php } else { ?>  placeholder="Email" <?php } ?>  style="color:#555;" />
			<input class="in" id="password1" type="password" name="password"  placeholder="Password" style="color:#555;"/>
			<input class="in" id="password2" type="password" name="password2" placeholder="Retype Password"  style="color:#555;" />
			<input class="in" type="text" name="name" placeholder="Your Company Name"  style="background:#eee; opacity:1;">
			<input type="hidden" name="level" value="<?php echo $level; ?>" />
			<input type='button' id="back" class="button4" value='Back' /><input id="submit" class="button4" type="submit" name="submit" value="Sign Up" />
		</div>
	</form>
	<br />
	<div class="text_clean">
		<strong> We'll help you get signed up: <span id="emphasis"><a href='tel:1-844-2738232' style='text-decoration:none;'>1 (844) BREVADA</a></span> </strong>
		   <br />
		Have an account? <a href="/home/login.php"><span style="color:#bc0101;">Click Here</span></a>
	</div>
</div>

<br />
<?php
//This is an include
$this->add(new View('../template/footer.php'));
?>