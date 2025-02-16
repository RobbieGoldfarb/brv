<?php
class TaskLive extends AbstractTask
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
		
		$feedLatest = max(@intval(Brevada::FromGET('latest')), 0);		
		$weeklyScores = Brevada::FromGET('scores') != 'daily';
		
		$HOUR = 3600; $DAY = $HOUR * 24; $WEEK = $DAY * 7; $MONTH = round(52*$WEEK / 12);
		
		/* Scores */
		$aspects = [];
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
					$aspects[] = ['id' => $a, 'AspectTypeID' => $b, 'Title' => $c];
				}
			}
			$stmt->close();
		}
		
		$scores = [];
		
		$day_up = [];
		$day_down = [];
		$week_up = [];
		$week_down = [];
		$all_up = [];
		$all_down = [];
		
		foreach($aspects as $row){
			$score = (new Data())->store($store)->from(time()-($weeklyScores ? $WEEK : $DAY))->to(time())->aspectType($row['AspectTypeID'])->getAvg();
			$scores[] = [
				'title' => $row['Title'],
				'percent' => round($score->getRating()),
				'id' => $row['id']
			];
			
			$day_aspect_now = (new Data())->store($store)->from(time()-$DAY)->to(time())->aspectType($row['AspectTypeID'])->getAvg();
			$day_aspect_then = (new Data())->store($store)->from(time()-($DAY*2))->to(time()-$DAY)->aspectType($row['AspectTypeID'])->getAvg();
			$day_aspect_change = $day_aspect_now->getRating() - $day_aspect_then->getRating();
			
			$week_aspect_now = (new Data())->store($store)->from(time()-$WEEK)->to(time())->aspectType($row['AspectTypeID'])->getAvg();
			$week_aspect_then = (new Data())->store($store)->from(time()-($WEEK*2))->to(time()-$WEEK)->aspectType($row['AspectTypeID'])->getAvg();
			$week_aspect_change = $week_aspect_now->getRating() - $week_aspect_then->getRating();
			
			// Change does not make sense when dealing with all time. Instead, we consider absolute.
			$all_aspect_data = (new Data())->store($store)->from(0)->to(time())->aspectType($row['AspectTypeID'])->getAvg();
			$all_aspect = $all_aspect_data->getRating();
			
			if($day_aspect_now->getSize() > 0 && $day_aspect_then->getSize() > 0){
				if($day_aspect_change > 0 && (empty($day_up) || $day_aspect_change > $day_up['percent'])){
					$day_up = [
						'title' => $row['Title'],
						'percent' => $day_aspect_change
					];
				} else if($day_aspect_change < 0 && (empty($day_down) || $day_aspect_change < $day_down['percent'])){
					$day_down = [
						'title' => $row['Title'],
						'percent' => $day_aspect_change
					];
				}
			}
			
			if($week_aspect_now->getSize() > 0 && $week_aspect_then->getSize() > 0){
				if($week_aspect_change > 0 && (empty($week_up) || $week_aspect_change > $week_up['percent'])){
					$week_up = [
						'title' => $row['Title'],
						'percent' => $week_aspect_change
					];
				} else if($week_aspect_change < 0 && (empty($week_down) || $week_aspect_change < $week_down['percent'])){
					$week_down = [
						'title' => $row['Title'],
						'percent' => $week_aspect_change
					];
				}
			}
			
			if($all_aspect_data->getSize() > 0){
				if(empty($all_up) || $all_aspect > $all_up['percent']){
					$all_up = [
						'title' => $row['Title'],
						'percent' => $all_aspect
					];
				} else if(empty($all_down) || $all_aspect < $all_down['percent']){
					$all_down = [
						'title' => $row['Title'],
						'percent' => $all_aspect
					];
				}
			}
		}
		
		$prev_day = (new Data())->store($store)->from(time()-($DAY*2))->to(time()-$DAY)->getAvg();
		$day = (new Data())->store($store)->from(time()-$DAY)->to(time())->getAvg();
		
		$day_average = round($day->getRating());
		$day_change = round($day->getRating() - $prev_day->getRating());
		$day_responses = $day->getSize();
		$day_bucket = ['data'=>[
			$day->get()[Data::TOTAL_FIVE_STAR],
			$day->get()[Data::TOTAL_FOUR_STAR],
			$day->get()[Data::TOTAL_THREE_STAR],
			$day->get()[Data::TOTAL_TWO_STAR],
			$day->get()[Data::TOTAL_ONE_STAR]
		]];
		
		$prev_week = (new Data())->store($store)->from(time()-($WEEK*2))->to(time()-$WEEK)->getAvg();
		$week = (new Data())->store($store)->from(time()-$WEEK)->to(time())->getAvg();
		
		$week_average = round($week->getRating());
		$week_change = round($week->getRating() - $prev_week->getRating());
		$week_responses = $week->getSize();
		$week_bucket = ['data'=>[
			$week->get()[Data::TOTAL_FIVE_STAR],
			$week->get()[Data::TOTAL_FOUR_STAR],
			$week->get()[Data::TOTAL_THREE_STAR],
			$week->get()[Data::TOTAL_TWO_STAR],
			$week->get()[Data::TOTAL_ONE_STAR]
		]];
		
		$all = (new Data())->store($store)->to(time())->getAvg();
		
		$all_average = round($all->getRating());
		$all_responses = $all->getSize();
		$all_bucket = ['data'=>[
			$all->get()[Data::TOTAL_FIVE_STAR],
			$all->get()[Data::TOTAL_FOUR_STAR],
			$all->get()[Data::TOTAL_THREE_STAR],
			$all->get()[Data::TOTAL_TWO_STAR],
			$all->get()[Data::TOTAL_ONE_STAR]
		]];
		
		$feed = [];
		
		if(($stmt = Database::prepare("
			SELECT
				`feedback`.`id`, `aspect_type`.`Title`, `feedback`.`Rating`,
				UNIX_TIMESTAMP(`feedback`.`Date`) as `Date`,
				`user_agents`.`UserAgent`
			FROM `feedback`
			JOIN `user_agents` ON `user_agents`.`id` = `feedback`.`UserAgentID`
			JOIN `aspects` ON `aspects`.`id` = `feedback`.`AspectID`
			JOIN `aspect_type` ON `aspect_type`.`id` = `aspects`.`AspectTypeID`
			JOIN `stores` ON `stores`.`id` = `aspects`.`StoreID`
			JOIN companies ON companies.`id` = stores.`CompanyID`
			WHERE
			`aspects`.`StoreID` = ? AND
			`aspects`.`Active` = 1 AND
			`stores`.`CompanyID` = ? AND
			`companies`.`Active` = 1 AND
			`companies`.`ExpiryDate` IS NOT NULL AND
			`companies`.`ExpiryDate` > NOW() AND
			`feedback`.`id` > ?
			ORDER BY `feedback`.`id` DESC LIMIT 15")) !== false){
			$stmt->bind_param('iii', $store, $company, $feedLatest);
			if($stmt->execute()){
				$stmt->bind_result($feedbackID, $aspectTitle, $feedbackRating, $feedbackDate, $userAgent);
				while($stmt->fetch()){
					$medium = 'desktop';
					if(preg_match('/'.TABLET_USERAGENT.'/', $userAgent)){
						$medium = 'tablet';
					} else if(preg_match('/mobile/i', $userAgent)){
						$medium = 'phone';
					}
					
					$feed[] = [
						'id' => $feedbackID,
						'percent' => round($feedbackRating),
						'aspect' => $aspectTitle,
						'date' => date('M jS, g:ia', $feedbackDate),
						'medium' => $medium
					];
				}
			}
			$stmt->close();
		}
		
		$this->data['live'] = [
			'snapshot' => [
				'day' => [
					'up' => $day_up,
					'down' => $day_down,
					'average' => $day_average,
					'change' => $day_change,
					'prevResponses' => $prev_day->getSize(),
					'responses' => $day_responses,
					'bucket' => $day_bucket
				],
				'week' => [
					'up' => $week_up,
					'down' => $week_down,
					'average' => $week_average,
					'change' => $week_change,
					'prevResponses' => $prev_week->getSize(),
					'responses' => $week_responses,
					'bucket' => $week_bucket
				],
				'all' => [
					'up' => $all_up,
					'down' => $all_down,
					'average' => $all_average,
					'responses' => $all_responses,
					'bucket' => $all_bucket
				]
			],
			'scores' => $scores,
			'scoresType' => $weeklyScores ? 'weekly' : 'daily',
			'feed' => $feed
		];
	}
}
?>