<?php
$this->addResource('/css/layout.css');
$this->addResource('/css/profile_header.css');
$this->addResource('/css/profile.css');
$this->addResource('/js/profile.js');

$tablet_id = $this->getParameter("tablet_id");
$tablet_url = $this->getParameter("tablet_url");

$id = @intval(Brevada::validate(empty($tablet_id) ? Brevada::FromPOSTGET('id') : $tablet_id));
$url_name=Brevada::validate(empty($tablet_url) ? Brevada::FromPOSTGET('name') : $tablet_url, VALIDATE_DATABASE);

$geo = Geography::GetGeo();
$country = $geo['country'];
$ip = $geo['ip'];

$condition = empty($url_name) ? "id = '".$_SESSION['StoreID']."'" : "URLName='{$url_name}'";

$query = Database::query("SELECT * FROM `stores` WHERE {$condition} LIMIT 1");

if($query->num_rows == 0) {
	Brevada::Redirect('/404');
}

if(empty($_SESSION['SessionCode'])){
	$_SESSION['SessionCode'] = strval(bin2hex(openssl_random_pseudo_bytes(16)));
}

$store_id='';
$name='';
$user_extension='';
$corporate='';

while($row=$query->fetch_assoc()){
   $store_id=Brevada::validate($row['id']);
   $name=Brevada::validate($row['Name']);
   $url_name=Brevada::validate($row['URLName']);
}

$this->setTitle("Give {$name} Feedback");

$this->addResource("<meta property='og:title' content='Give us feedback!'/>", true, true);
$this->addResource("<meta property='og:type' content='website'/>", true, true);
$this->addResource("<meta property='og:url' content='http://brevada.com/{$url_name}'/>", true, true);
$this->addResource("<meta property='og:image' content='http://brevada.com/images/square_logo.png'/>", true, true);
$this->addResource("<meta property='og:site_name' content='Brevada'/>", true, true);
$this->addResource("<meta property='og:description' content='Give {$name} Feedback on Brevada'/>", true, true);
?>

	
<div class="topbar">
	<div class="container">
		<img class="logo" src="/images/quote.png" />
		<div class="title">
			<?php echo sprintf(__('Give %s Feedback'), "<b>{$name}</b>"); ?>
		</div>
		<i class="fa fa-arrow-circle-down"></i>
	</div>
</div>
<div class="top-spacer"></div>

<div id="aspects" class="aspect-container container">
	<?php
	$postQuery=Database::query("SELECT aspects.ID, aspect_type.Title, aspect_type.Description as Description FROM aspects LEFT JOIN aspect_type ON aspect_type.ID = aspects.AspectTypeID WHERE aspects.StoreID = {$store_id} AND aspects.`Active` = 1 ORDER BY RAND()");
	if($postQuery !== false && $postQuery->num_rows > 0){
		while($row=$postQuery->fetch_assoc()) {		
			$this->add(new View('../widgets/profile/post_box.php', array('row' => $row, 'id' => $store_id)));
		}
	}
	?>	
	<div class="bottom-spacer"></div>
</div>

<div class="fixed-toolbar">
	<div class="container">
		<div class="submit" id='imdone'><i class="fa fa-check"></i> <?php _e("I'm Done"); ?></div>
	</div>
</div>

<div id="email_connect"  class="aspect-container container" style="display: none;">
	<?php $this->add(new View('../widgets/profile/email_connect.php', array('store_id' => $store_id))); ?>
</div>

<?php
	// Don't ask for post/pre data if already collected.
	if(($stmt = Database::prepare("
		SELECT 1 FROM `session_data` 
		WHERE `SessionCode` = ? 
		AND NOT EXISTS (
			SELECT 1 FROM store_features JOIN stores ON stores.FeaturesID = store_features.id
			WHERE stores.id = ? AND store_features.SessionCheck = 0
			LIMIT 1
		)
		LIMIT 1
	")) !== false){
		$stmt->bind_param('si', $_SESSION['SessionCode'], $store_id);
		if($stmt->execute()){
			$stmt->store_result();
			if($stmt->num_rows == 0){
				$this->add(new View('../widgets/profile/data_collection.php', array('store_id' => $store_id)));
			}
		}
		$stmt->close();
	}
?>