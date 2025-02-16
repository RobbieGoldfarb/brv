<?php
class TaskPlayground extends AbstractTask
{
	private $data;
	
	public function execute($method, $tasks, &$data)
	{
		if($method == 'get'){
			if(!TaskLoader::requiresData(['localtime'], $_GET)){
				throw new Exception("Incomplete request.");
			}
		}
		if(!Brevada::IsLoggedIn()){
			throw new Exception("Authentication required.");
		}
		$this->data = &$data;
	}
	
	public function taskAll(){
		$store = @intval(Brevada::FromGET('store'));
		if(empty($store)){
			throw new Exception("Incomplete request: 'store' required.");
		}
		
		if(!$_SESSION['Corporate'] && $store != $_SESSION['StoreID']){
			throw new Exception("Invalid 'store'.");
		}
		
		$company = $_SESSION['CompanyID'];
		
		$keywords = [];
		if(($stmt = Database::prepare("
			SELECT company_keywords_link.CompanyKeywordID
			FROM company_keywords_link
			WHERE
			company_keywords_link.`CompanyID` = ?")) !== false){
			$stmt->bind_param('i', $company);
			if($stmt->execute()){
				$stmt->bind_result($keywordID);
				while($stmt->fetch()){
					$keywords = @intval($keywordID);
				}
			}
			$stmt->close();
		}
		
		$rows = [];
		
		if(($stmt = Database::prepare("
			SELECT `aspects`.`id`, `aspect_type`.`id` as `AspectTypeID`,
			`aspect_type`.`Title`
			FROM `aspects`
			JOIN `aspect_type` ON `aspect_type`.`id` = `aspects`.`AspectTypeID`
			JOIN `stores` ON `stores`.`id` = `aspects`.`StoreID`
			JOIN companies ON companies.`id` = stores.`CompanyID`
			WHERE
			`aspects`.`StoreID` = ? AND
			`aspects`.`Active` = 1 AND
			`stores`.`CompanyID` = ? AND
			`companies`.`Active` = 1 AND
			`companies`.`ExpiryDate` IS NOT NULL AND
			`companies`.`ExpiryDate` > NOW()
			ORDER BY `aspect_type`.`Title`")) !== false){
			$stmt->bind_param('ii', $store, $company);
			if($stmt->execute()){
				$stmt->bind_result($a, $b, $c);
				while($stmt->fetch()){
					$rows[] = ['id' => $a, 'AspectTypeID' => $b, 'Title' => $c];
				}
			}
			$stmt->close();
		}
		
		$HOUR = 3600; $DAY = $HOUR * 24; $WEEK = $DAY * 7; $MONTH = 52*$WEEK / 12;
		
		$minDate = time() - $MONTH;
		
		$from = @intval(Brevada::FromGET('from'));
		$to = @intval(Brevada::FromGET('to'));
		if($to == 0){ $to = time(); }
		
		$included = empty($_GET['included']) ? [] : array_map('intval', explode(',', $_GET['included']));
		if(!isset($_GET['included'])){ $included = false; }
		
		if($from == 0){
			foreach($rows as $row){
				if($included !== false && !in_array(intval($row['id']), $included)){
					continue;
				}
				$overall = (new Data())->store($store)->aspectType($row['AspectTypeID'])->getAvg();
				$minDate = min($overall->getSize() > 0 ? $overall->getUTCFrom() : $minDate, $minDate);
			}
			$from = $minDate;
		}
		
		$dateFormat = 'g:i:s a';
		$delta = $to - $from;
		if($delta >= $MONTH*12 - $DAY){
			if(date('Y', $from) != date('Y', $to)){
				$dateFormat = 'M, Y';
			} else {
				$dateFormat = 'M';
			}
		} else if($delta > $DAY){
			$dateFormat = 'M jS';
		} else if($delta > 60*30){
			$dateFormat = 'g:i a';
		}
		
		$includedTypes = [];
		
		$bucketSize = 12;
		$interval = floor($delta / $bucketSize);
		
		$aspects = [];
		foreach($rows as $row){			
			$aspectType = $row['AspectTypeID'];
			
			if($included !== false && !in_array(intval($row['id']), $included)){
				$aspects[] = [
					"id" => $row['id'],
					"title" => __($row['Title'])
				];
				continue;
			}
			
			$includedTypes[] = intval($aspectType);
			
			$overall = (new Data())->store($store)->aspectType($aspectType)->getAvg();
			$minDate = min($overall->getSize() > 0 ? $overall->getUTCFrom() : $minDate, $minDate);
			
			$rating = (new Data())->store($store)->aspectType($aspectType)->from($from)->to($to)->getAvg();
			
			$bucketDates_rel = [];
			$bucketData_rel = [];
			
			$minBucket_rel = 100;
			$maxBucket_rel = -100;
			
			$prevVal = (new Data())->store($store)->aspectType($aspectType)->from(0)->to($from)->getAvg()->getRating();
			for($i = 0; $i < $bucketSize; $i++){
				$intvEnd = $from + ($i+1)*$interval - 1;
				
				$bucketDates_rel[] = date($dateFormat, $intvEnd);
				
				$intvRating = (new Data())->store($store)->aspectType($aspectType)->from(0)->to($intvEnd)->getAvg();
				
				if($intvRating->getSize() > 0){
					$val = $intvRating->getRating() - $prevVal;
					$prevVal = $intvRating->getRating();
				} else {
					$val = 0;
				}
				$bucketData_rel[] = $val;
				
				$minBucket_rel = min($minBucket_rel, $val);
				$maxBucket_rel = max($maxBucket_rel, $val);
			}
			
			$bucketDates_abs = [];
			$bucketData_abs = [];
			$bucketData_responses = [];
			
			$minBucket_abs = 100;
			$maxBucket_abs = 0;
			$responses_max = 0;
			
			if(!empty($includedTypes)){
				$aspectAvg = (new Data())->store($store)->aspectType($aspectType)->from($from)->to($to)->getAvg($bucketSize);
				$prevVal = (new Data())->store($store)->aspectType($aspectType)->from($from - ($MONTH*12))->to($from)->getAvg()->getRating();
				for($i = 0; $i < $bucketSize; $i++){
					$bucketDates_abs[] = date($dateFormat, $aspectAvg->getUTCFrom($i)) == date($dateFormat, $aspectAvg->getUTCTo($i)-1) ? date($dateFormat, $aspectAvg->getUTCFrom($i)) : date($dateFormat, $aspectAvg->getUTCFrom($i)) . ' - ' . date($dateFormat, $aspectAvg->getUTCTo($i)-1);
					
					$bucketData_responses[] = $aspectAvg->getSize($i);
					$responses_max = max($responses_max, $aspectAvg->getSize($i));
					
					$value = $aspectAvg->getRating($i);
					if($aspectAvg->getSize($i) == 0){
						$value = $prevVal;
					} else {
						$prevVal = $value;
					}
					$bucketData_abs[] = $value;
					
					$minBucket_abs = min($minBucket_abs, $value);
					$maxBucket_abs = max($maxBucket_abs, $value);
				}
			}
			
			$aspects[] = [
				"id" => $row['id'],
				"title" => __($row['Title']),
				"all" => [
					"size" => $overall->getSize(),
					"percent" => $overall->getRating()
				],
				"bucket" => [
					"size" => $rating->getSize(),
					"average" => $rating->getRating(),
					"rel" => [
						"labels" => $bucketDates_rel,
						"data" => $bucketData_rel,
						"min" => $minBucket_rel,
						"max" => $maxBucket_rel
					],
					"abs" => [
						"labels" => $bucketDates_abs,
						"data" => $bucketData_abs,
						"responses" => [
							"data" => $bucketData_responses,
							"max" => $responses_max
						],
						"min" => $minBucket_abs,
						"max" => $maxBucket_abs
					]
				]
			];
		}
		
		$bucketDates = [];
		$bucketData = [];
		
		$minBucket = 100;
		$maxBucket = 0;
		
		if(!empty($includedTypes)){
			$combined = (new Data())->store($store)->aspectType($includedTypes)->from($from)->to($to)->getAvg($bucketSize);
			$prevVal = (new Data())->store($store)->aspectType($includedTypes)->from($from - ($MONTH*12))->to($from)->getAvg()->getRating();
			for($i = 0; $i < $bucketSize; $i++){
				$bucketDates[] = date($dateFormat, $combined->getUTCFrom($i)) == date($dateFormat, $combined->getUTCTo($i)-1) ? date($dateFormat, $combined->getUTCFrom($i)) : date($dateFormat, $combined->getUTCFrom($i)) . ' - ' . date($dateFormat, $combined->getUTCTo($i)-1);
				
				$value = $combined->getRating($i);
				if($combined->getSize($i) == 0){
					$value = $prevVal;
				} else {
					$prevVal = $value;
				}
				$bucketData[] = $value;
				
				$minBucket = min($minBucket, $value);
				$maxBucket = max($maxBucket, $value);
			}
		}
		
		$milestones = [];
		$financials = [];
		
		$this->data['playground'] = [
			"aspects" => $aspects,
			"milestones" => $milestones,
			"financials" => $financials,
			"average" => [
				"labels" => $bucketDates,
				"bucket" => $bucketData,
				"min" => $minBucket,
				"max" => $maxBucket
			],
			"minDate" => $minDate
		];
	}
}
?>