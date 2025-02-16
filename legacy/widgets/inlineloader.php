<?php
$this->addResource('/css/layout.css');
$this->addResource('/css/loader.css');
$this->addResource("<meta name='viewport' content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0'/>", true, true);
$loaderDestination = $this->getParameter('destination');
$loaderWait = empty($this->getParameter('wait')) ? '600' : $this->getParameter('wait');
?>

<div id='load-container'>
	<div>
		<div id="loader">
		  <ul>
		    <li class='loader-1'></li>
		    <li class='loader-2'></li>
		    <li class='loader-3'></li>
		    <li class='loader-4'></li>
		    <li class='loader-5'></li>
		    <li class='loader-6'></li>
		  </ul>
		</div>
	</div>
</div>

<script type='text/javascript'>
$(document).ready(function(){
	setTimeout(function(){
		$.ajax({
			url : '<?php echo $loaderDestination; ?>',
			cache: false
		}).done(function(data){
			document.open();
			document.write(data);
			document.close();
			$('body').hide().fadeIn();
		});
	}, <?php echo $loaderWait; ?>);
});
</script>