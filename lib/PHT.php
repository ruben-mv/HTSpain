<?php
/**
 * PHT 1.14.1 - 2009-10-19 - Telesphore <mail@telesphore.org>
 *
 * @author Telesphore <mail@telesphore.org>
 * @link http://www.telesphore.org
 * @version 1.14.1
 * @license http://www.php.net/license/3_0.txt
 */

/** */
class CHPPConnection
{
	//---global---
	private $agent;
	private $chppId;
	private $chppKey;
	private $host;
	private $instance;
	private $dom;
	private $loginResult;
	private $cookies = array();
	//------------

	//---data---
	private $club = null;
	private $teams = array();
	private $teamsUserid = array();
	private $economy = null;
	private $regions = array();
	private $leagues = array();
	private $leaguesSeason = array();
	private $worldDetails = null;
	private $worldLanguages = null;
	private $training = null;
	private $trainingStats = array();
	private $teamsTransfersHistory = array();
	private $playersTransfersHistory = array();
	private $players = array();
	private $teamsPlayers = array();
	private $teamsOldPlayers = array();
	private $teamsOldCoaches = array();
	private $matches = array();
	private $teamsMatches = array();
	private $youthMatches = array();
	private $youthTeamsMatches = array();
	private $matchesDetails = array();
	private $youthMatchesDetails = array();
	private $teamsArchivesMatches = array();
	private $youthTeamsArchivesMatches = array();
	private $lineups = array();
	private $youthLineups = array();
	private $liveNumber = null;
	private $liveMatches = null;
	private $live = null;
	private $liveAllEvents = false;
	private $nationalAteams = null;
	private $nationalU20teams = null;
	private $nationalAmatches = null;
	private $nationalU20matches = null;
	private $nationalTeamDetails = array();
	private $nationalPlayers = array();
	private $nationalPlayersStats = array();
	private $nationalPlayersStatsWC = array();
	private $worldCupGroups = array();
	private $worldCupMatches = array();
	private $alliancesName = array();
	private $alliancesAbbr = array();
	private $alliancesDesc = array();
	private $alliancesIds = array();
	private $allianceDetails = array();
	private $allianceMembers = array();
	private $allianceMembersLetters = array();
	private $allianceRoles = array();
	private $allianceRoleDetails = array();
	private $arenas = array();
	private $arenasStats = array();
	private $myArenaStats = array();
	private $matchOrders = array();
	private $challanges = null;
	private $cups = array();
	private $searchTeamId = array();
	private $searchTeamName = array();
	private $searchUserName = array();
	private $searchPlayerId = array();
	private $searchPlayerName = array();
	private $searchRegionName = array();
	private $searchArenaName = array();
	private $searchMatchId = array();
	private $searchSeriesName = array();
	private $searchSeriesId = array();
	private $bookmarks = array();
	private $bookmarksTeams = null;
	private $bookmarksPlayers = null;
	private $bookmarksMatches = null;
	private $bookmarksConfUsers = null;
	private $bookmarksLeagues = null;
	private $bookmarksYouthTeams = null;
	private $bookmarksYouthPlayers = null;
	private $bookmarksYouthMatches = null;
	private $bookmarksYouthLeagues = null;
	private $bookmarksConfPosts = null;
	private $bookmarksConfThreads = null;
	private $trainingEvents = array();
	private $playerEvents = array();
	private $fans = null;
	private $achievements = array();
	private $flagsHome = array();
	private $flagsAway = array();
	private $flagsPlayers = array();
	private $flagsRaisedPlayers = array();
	//----------

	//---url---
	const SERVER_URL					= 'http://www.hattrick.org';
	const STAGE_URL						= 'http://stage.hattrick.org';
	const CHPP_URL						= '/Common/chppxml.axd';
	const ERROR_FILE					= 'chpperror.xml';
	//---------

	/**
	 * Create a CHPP connection to Hattrick
	 *
	 * @param String $chppName
	 * @param String $chppId
	 * @param String $chppKey
	 */
	public function __construct($chppName, $chppId, $chppKey)
	{
		$this->agent = $chppName;
		$this->chppId = $chppId;
		$this->chppKey = $chppKey;
		$url = $this->buildUrl(self::SERVER_URL.self::CHPP_URL , array('file'=>'servers', 'version'=>'1.2'));
		$result = $this->fetchUrl($url);
		$this->dom = new DOMDocument('1.0', 'UTF-8');
		$this->dom->loadXML($result);
		$this->host = $this->dom->getElementsByTagName('RecommendedURL')->item(0)->nodeValue;
	}

	/**
	 * Connect to Hattrick Stage Server, only to debug or test new features, may be broken at any time, be careful !
	 */
	public function useStageAtYourOwnRisk()
	{
		$this->host = self::STAGE_URL;
	}

	/**
	 * Connect an user
	 *
	 * @param String $login
	 * @param String $securityCode
	 * @return Boolean
	 */
	public function connectUser($login, $securityCode)
	{
		$params = array('file'=>'login',
										'version'=>'1.1',
										'actionType'=>'login',
										'loginname'=>$login,
										'readonlypassword'=>$securityCode,
										'chppID'=>$this->chppId,
										'chppKey'=>$this->chppKey);
		$url = $this->buildUrl(self::CHPP_URL, $params);
		$login = $this->fetchUrl($url);
		$this->checkXmlData($login);
		$this->dom->loadXML($login);
		$node = $this->dom->getElementsByTagName('LoginResult');
		if($node !== null && $node->length)
		{
			$this->loginResult = $node->item(0)->nodeValue;
			if($this->loginResult == 0)
			{
				return true;
			}
		}
		return false;
	}

	/**
	 * Return login result code
	 *
	 * @return Integer
	 */
	public function getLoginResult()
	{
		return $this->loginResult;
	}

	/**
	 * Disconnect from Hattrick
	 */
	public function disconnect()
	{
		$url = $this->buildUrl(self::CHPP_URL, array('file'=>'login', 'actionType'=>'logout', 'version'=>'1.1'));
		$this->fetchUrl($url);
		$this->cookies = array();
	}

	/**
	 * Load data of user's club
	 *
	 * @return HTClub
	 */
	public function getClub()
	{
		if(!isset($this->club) || $this->club === null)
		{
			$url = $this->buildUrl(self::CHPP_URL, array('file'=>'club', 'version'=>'1.1'));
			$this->club = new HTClub($this->fetchUrl($url));
		}
		return $this->club;
	}

	/**
	 * Clear cache of club
	 */
	public function clearClub()
	{
		$this->club = null;
	}

	/**
	 * Get data of user's team by default or of teamId if given
	 *
	 * @param Integer $id
	 * @return HTTeam
	 */
	public function getTeam($id = null)
	{
		if($id === null)
		{
			$id = $this->getClub()->getTeamId();
		}
		if(!isset($this->teams[$id]) || $this->teams[$id] === null)
		{
			$url = $this->buildUrl(self::CHPP_URL, array('file'=>'team', 'teamID'=>$id, 'version'=>'2.1'));
			$this->teams[$id] = new HTTeam($this->fetchUrl($url));
		}
		return $this->teams[$id];
	}

	/**
	 * Delete cache of team data
	 *
	 * @param Integer $id
	 */
	public function clearTeam($id = null)
	{
		if($id === null)
		{
			$id = $this->getClub()->getTeamId();
		}
		$this->teams[$id] = null;
	}

	/**
	 * Clear cache of all teams
	 */
	public function clearTeams()
	{
		$this->teams = array();
	}

	/**
	 * Get data of user's team by default or of userId if given
	 *
	 * @param Integer $id
	 * @return HTTeam
	 */
	public function getTeamByUserId($id = null)
	{
		if($id === null)
		{
			$id = $this->getClub()->getUserId();
		}
		if(!isset($this->teamsUserid[$id]) || $this->teamsUserid[$id] === null)
		{
			$url = $this->buildUrl(self::CHPP_URL, array('file'=>'team', 'userID'=>$id, 'version'=>'2.1'));
			$this->teamsUserid[$id] = new HTTeam($this->fetchUrl($url));
		}
		return $this->teamsUserid[$id];
	}

	/**
	 * Delete cache of team by userid data
	 *
	 * @param Integer $id
	 */
	public function clearTeamByUserId($id = null)
	{
		if($id === null)
		{
			$id = $this->getClub()->getUserId();
		}
		$this->teamsUserid[$id] = null;
	}

	/**
	 * Clear cache of all teams by user id
	 */
	public function clearTeamsByUserId()
	{
		$this->teamsUserid = array();
	}

	/**
	 * Get economy data of user's club, converted in country currency if specfied
	 *
	 * @param Integer $countryCurrency (Constant taken from HTMoney class)
	 * @return HTEconomy
	 */
	public function getEconomy($countryCurrency = null)
	{
		if(!isset($this->economy) || $this->economy === null)
		{
			$url = $this->buildUrl(self::CHPP_URL, array('file'=>'economy', 'version'=>'1.1'));
			$this->economy = new HTEconomy($this->fetchUrl($url), $countryCurrency);
		}
		return $this->economy;
	}

	/**
	 * Clear cache of economy
	 */
	public function clearEconomy()
	{
		$this->economy = null;
	}

	/**
	 * Return HTRegion object
	 *
	 * @param Integer $regionId
	 * @return HTRegion
	 */
	public function getRegion($regionId = null)
	{
		if($regionId === null)
		{
			$regionId = $this->getTeam()->getRegionId();
		}
		if(!isset($this->regions[$regionId]) || $this->regions[$regionId] === null)
		{
			$url = $this->buildUrl(self::CHPP_URL, array('file'=>'regiondetails', 'regionID'=>$regionId, 'version'=>'1.2'));
			$this->regions[$regionId] = new HTRegion($this->fetchUrl($url));
		}
		return $this->regions[$regionId];
	}

	/**
	 * Clear cache of region
	 */
	public function clearRegion($regionId = null)
	{
		if($regionId === null)
		{
			$regionId = $this->getTeam()->getRegionId();
		}
		$this->regions[$regionId] = null;
	}

	/**
	 * Clear cache of all regions
	 */
	public function clearAllRegions()
	{
		$this->regions= array();
	}

	/**
	 * Return a HTLeague object
	 *
	 * @param Integer $id
	 * @return HTLeague
	 */
	public function getLeague($id = null)
	{
		if($id === null)
		{
			$id = $this->getTeam()->getLeagueLevelId();
		}
		if(!isset($this->leagues[$id]) || $this->leagues[$id] === null)
		{
			$url = $this->buildUrl(self::CHPP_URL, array('file'=>'leaguedetails', 'leagueLevelUnitID'=>$id, 'version'=>'1.2'));
			$this->leagues[$id] = new HTLeague($this->fetchUrl($url));
		}
		return $this->leagues[$id];
	}

	/**
	 * Delete cache of league
	 *
	 * @param Integer $id
	 */
	public function clearLeague($id = null)
	{
		if($id === null)
		{
			$id = $this->getTeam()->getLeagueLevelId();
		}
		$this->leagues[$id] = null;
	}

	/**
	 * Delete cache of all leagues
	 */
	public function clearLeagues()
	{
		$this->leagues = array();
	}

	/**
	 * Return a HTLeagueSeason object
	 *
	 * @param Integer $leagueLevelId
	 * @param Integer $season
	 * @return HTLeagueSeason
	 */
	public function getLeagueSeason($leagueLevelId = null, $season = null)
	{
		if($leagueLevelId === null)
		{
			$leagueLevelId = $this->getLeague()->getLeagueLevelId();
		}
		if(!isset($this->leaguesSeason[$leagueLevelId][$season]) || $this->leaguesSeason[$leagueLevelId][$season] === null)
		{
			$params = array('file'=>'leaguefixtures', 'leagueLevelUnitID'=>$leagueLevelId, 'version'=>'1.2');
			if($season !== null)
			{
				$params['season'] = $season;
			}
			$url = $this->buildUrl(self::CHPP_URL, $params);
			$this->leaguesSeason[$leagueLevelId][$season] = new HTLeagueSeason($this->fetchUrl($url));
		}
		return $this->leaguesSeason[$leagueLevelId][$season];
	}

	/**
	 * Delete cache of league fixture
	 *
	 * @param Integer $leagueLevelId
	 * @param Integer $season
	 */
	public function clearLeagueSeason($leagueLevelId = null, $season = null)
	{
		if($leagueLevelId === null)
		{
			$leagueLevelId = $this->getLeague()->getLeagueLevelId();
		}
		if($season === null)
		{
			$season = $this->getLeagueSeason()->getSeason();
		}
		$this->leaguesSeason[$leagueLevelId][$season] = null;
	}

	/**
	 * Delete all leagues fixture caches
	 */
	public function clearLeaguesSeasons()
	{
		$this->leaguesSeason = array();
	}

	/**
	 * Return HTWorldDetails object
	 *
	 * @return HTWorldDetails
	 */
	public function getWorldDetails()
	{
		if(!isset($this->worldDetails) || $this->worldDetails === null)
		{
			$url = $this->buildUrl(self::CHPP_URL, array('file'=>'worlddetails', 'version'=>'1.2'));
			$this->worldDetails = new HTWorldDetails($this->fetchUrl($url));
		}
		return $this->worldDetails;
	}

	/**
	 * Delete cache of world detail
	 */
	public function clearWorldDetails()
	{
		$this->worldDetails = null;
	}

	/**
	 * Return HTWorldLanguages object
	 *
	 * @return HTWorldLanguages
	 */
	public function getWorldLanguages()
	{
		if(!isset($this->worldLanguages) || $this->worldLanguages === null)
		{
			$url = $this->buildUrl(self::CHPP_URL, array('file'=>'worldlanguages', 'version'=>'1.2'));
			$this->worldLanguages = new HTWorldLanguages($this->fetchUrl($url));
		}
		return $this->worldLanguages;
	}

	/**
	 * Delete cache of world langauges
	 */
	public function clearWorldLanguages()
	{
		$this->worldLanguages = null;
	}

	/**
	 * Return HTTraining object
	 *
	 * @return HTTraining
	 */
	public function getTraining()
	{
		if(!isset($this->training) || $this->training === null)
		{
			$url = $this->buildUrl(self::CHPP_URL , array('file'=>'training', 'version'=>'1.4'));
			$this->training = new HTTraining($this->fetchUrl($url));
		}
		return $this->training;
	}

	/**
	 * Delete cache of training
	 */
	public function clearTraining()
	{
		$this->training = null;
	}

	/**
	 * Return HTTrainingStats object
	 *
	 * @param Integer $leagueId
	 * @return HTTrainingStats
	 */
	public function getTrainingStats($leagueId = null)
	{
		if(!isset($this->trainingStats[$leagueId]) || $this->trainingStats[$leagueId] === null)
		{
			if($leagueId === null)
			{
				$leagueId = $this->getLeague()->getLeagueId();
			}
			$url = $this->buildUrl(self::CHPP_URL, array('file'=>'training', 'actionType'=>'stats', 'LeagueID'=>$leagueId, 'version'=>'1.4'));
			$this->trainingStats[$leagueId] = new HTTrainingStats($this->fetchUrl($url));
		}
		return $this->trainingStats[$leagueId];
	}

	/**
	 * Delete training stats cache of league id
	 *
	 * @param unknown_type $leagueId
	 */
	public function clearTrainingStats($leagueId = null)
	{
		$this->trainingStats[$leagueId] = null;
	}

	/**
	 * Delete all caches of training stats
	 */
	public function clearTrainingsStats()
	{
		$this->trainingStats = array();
	}

	/**
	 * Return HTTeamTransferHistory object
	 *
	 * @param Integer $teamId
	 * @param Integer $pageIndex
	 * @return HTTeamTransferHistory
	 */
	public function getTeamTransfersHistory($teamId = null, $pageIndex = 1)
	{
		if($teamId === null)
		{
			$teamId = $this->getTeam()->getTeamId();
		}
		if(!isset($this->teamsTransfersHistory[$teamId][$pageIndex]) || $this->teamsTransfersHistory[$teamId][$pageIndex] === null)
		{
			$url = $this->buildUrl(self::CHPP_URL , array('file'=>'transfersTeam', 'teamID'=>$teamId, 'pageIndex'=>$pageIndex, 'version'=>'1.1'));
			$this->teamsTransfersHistory[$teamId][$pageIndex] = new HTTeamTransferHistory($this->fetchUrl($url));
		}
		return $this->teamsTransfersHistory[$teamId][$pageIndex];
	}

	/**
	 * Delete cache of team transfers history
	 *
	 * @param Integer $teamId
	 */
	public function clearTeamTransfersHistory($teamId = null)
	{
		if($teamId === null)
		{
			$teamId = $this->getTeam()->getTeamId();
		}
		$this->teamsTransfersHistory[$teamId] = null;
	}

	/**
	 * Delete all caches of teams transfers history
	 */
	public function clearTeamsTransfersHistory()
	{
		$this->teamsTransfersHistory = array();
	}

	/**
	 * Return HTPlayerTransferHistory object
	 *
	 * @param Integer $playerId
	 * @return HTPlayerTransferHistory
	 */
	public function getPlayerTransfersHistory($playerId)
	{
		if(!isset($this->playersTransfersHistory[$playerId]) || $this->playersTransfersHistory[$playerId] === null)
		{
			$url = $this->buildUrl(self::CHPP_URL, array('file'=>'transfersPlayer', 'playerID'=>$playerId, 'version'=>'1.1'));
			$this->playersTransfersHistory[$playerId] = new HTPlayerTransferHistory($this->fetchUrl($url));
		}
		return $this->playersTransfersHistory[$playerId];
	}

	/**
	 * Delete cache of player transfers history
	 *
	 * @param Integer $playerId
	 */
	public function clearPlayerTransfersHistory($playerId)
	{
		$this->teamsTransfersHistory[$playerId] = null;
	}

	/**
	 * Delete all caches of players transfers history
	 */
	public function clearPlayersTransfersHistory()
	{
		$this->playersTransfersHistory = array();
	}

	/**
	 * Return HTTeamPlayers object
	 *
	 * @param Integer $teamId
	 * @return HTTeamPlayers
	 */
	public function getTeamPlayers($teamId = null)
	{
		if($teamId === null)
		{
			$teamId = $this->getTeam()->getTeamId();
		}
		if(!isset($this->teamsPlayers[$teamId]) || $this->teamsPlayers[$teamId] === null)
		{
			$url = $this->buildUrl(self::CHPP_URL, array('file'=>'players', 'version'=>'1.6', 'actionType'=>'view', 'teamID'=>$teamId));
			$this->teamsPlayers[$teamId] = new HTTeamPlayers($this->fetchUrl($url));
		}
		return $this->teamsPlayers[$teamId];
	}

	/**
	 * Delete cache of team players list
	 *
	 * @param Integer $teamId
	 */
	public function clearTeamPlayers($teamId = null)
	{
		if($teamId === null)
		{
			$teamId = $this->getTeam()->getTeamId();
		}
		$this->teamsPlayers[$teamId] = null;
	}

	/**
	 * Delete all caches of teams players
	 */
	public function clearTeamsPlayers()
	{
		$this->teamsPlayers = array();
	}

	/**
	 * Return HTTeamOldPlayers object
	 *
	 * @param Integer $teamId
	 * @return HTTeamOldPlayers
	 */
	public function getTeamOldPlayers($teamId = null)
	{
		if($teamId === null)
		{
			$teamId = $this->getTeam()->getTeamId();
		}
		if(!isset($this->teamsOldPlayers[$teamId]) || $this->teamsOldPlayers[$teamId] === null)
		{
			$url = $this->buildUrl(self::CHPP_URL, array('file'=>'players', 'version'=>'1.6', 'actionType'=>'viewOldies', 'teamID'=>$teamId));
			$this->teamsOldPlayers[$teamId] = new HTTeamOldPlayers($this->fetchUrl($url));
		}
		return $this->teamsOldPlayers[$teamId];
	}

	/**
	 * Delete cache of team old players list
	 *
	 * @param Integer $teamId
	 */
	public function clearTeamOldPlayers($teamId = null)
	{
		if($teamId === null)
		{
			$teamId = $this->getTeam()->getTeamId();
		}
		$this->teamsOldPlayers[$teamId] = null;
	}

	/**
	 * Delete all caches of teams old players
	 */
	public function clearTeamsOldPlayers()
	{
		$this->teamsOldPlayers = array();
	}

	/**
	 * Return HTTeamOldCoaches object
	 *
	 * @param Integer $teamId
	 * @return HTTeamOldCoaches
	 */
	public function getTeamOldCoaches($teamId = null)
	{
		if($teamId === null)
		{
			$teamId = $this->getTeam()->getTeamId();
		}
		if(!isset($this->teamsOldCoaches[$teamId]) || $this->teamsOldCoaches[$teamId] === null)
		{
			$url = $this->buildUrl(self::CHPP_URL, array('file'=>'players', 'version'=>'1.6', 'actionType'=>'viewOldCoaches', 'teamID'=>$teamId));
			$this->teamsOldCoaches[$teamId] = new HTTeamOldCoaches($this->fetchUrl($url));
		}
		return $this->teamsOldCoaches[$teamId];
	}

	/**
	 * Delete cache of team old coaches list
	 *
	 * @param Integer $teamId
	 */
	public function clearTeamOldCoaches($teamId = null)
	{
		if($teamId === null)
		{
			$teamId = $this->getTeam()->getTeamId();
		}
		$this->teamsOldCoaches[$teamId] = null;
	}

	/**
	 * Delete all caches of teams old coaches
	 */
	public function clearTeamsOldCoaches()
	{
		$this->teamsOldCoaches = array();
	}

	/**
	 * Return HTPlayer object
	 *
	 * @param Integer $playerId
	 * @return HTPlayer
	 */
	public function getPlayer($playerId)
	{
		if(!isset($this->players[$playerId]) || $this->players[$playerId] === null)
		{
			$url = $this->buildUrl(self::CHPP_URL, array('file'=>'playerdetails', 'version'=>'1.4', 'playerID'=>$playerId));
			$this->players[$playerId] = new HTPlayer($this->fetchUrl($url));
		}
		return $this->players[$playerId];
	}

	/**
	 * Delete cache of a player
	 *
	 * @param Integer $playerId
	 */
	public function clearPlayer($playerId)
	{
		$this->players[$playerId] = null;
	}

	/**
	 * Delete cache of all players
	 */
	public function clearPlayers()
	{
		$this->players = array();
	}

	/**
	 * Return HTTeamMatches object
	 *
	 * @param Integer $teamId
	 * @param String $showBeforeDate (format should be : yyyy-mm-dd  - If no specify : returned matches are from now - 28 days to now + 28 days)
	 * @return HTTeamMatches
	 */
	public function getSeniorTeamMatches($teamId = null, $showBeforeDate = null)
	{
		if($showBeforeDate === null || ereg('^[0-9]{4}-[0-9]{2}-[0-9]{2}$', $showBeforeDate))
		{
			if($teamId === null)
			{
				$teamId = $this->getTeam()->getTeamId();
			}
			if(!isset($this->teamsMatches[$teamId][strtotime($showBeforeDate)]) || $this->teamsMatches[$teamId][strtotime($showBeforeDate)] === null)
			{
				$url = $this->buildUrl(self::CHPP_URL, array('file'=>'matches', 'teamID'=>$teamId, 'LastMatchDate'=>$showBeforeDate, 'version'=>'2.3'));
				$this->teamsMatches[$teamId][strtotime($showBeforeDate)] = new HTTeamMatches($this->fetchUrl($url));
			}
			return $this->teamsMatches[$teamId][strtotime($showBeforeDate)];
		}
		return null;
	}

	/**
	 * Delete cache matches of team
	 *
	 * @param Integer $teamId
	 */
	public function clearSeniorTeamMatches($teamId = null)
	{
		if($teamId === null)
		{
			$teamId = $this->getTeam()->getTeamId();
		}
		$this->teamsMatches[$teamId] = null;
	}

	/**
	 * Delete all caches of teams matches
	 */
	public function clearSeniorTeamsMatches()
	{
		$this->teamsMatches = array();
	}

	/**
	 * Return HTTeamMatches object
	 *
	 * @param Integer $youthTeamId
	 * @param String $showBeforeDate (format should be : yyyy-mm-dd  - If no specify : returned matches are from now - 28 days to now + 28 days)
	 * @return HTTeamMatches
	 */
	public function getYouthTeamMatches($youthTeamId = null, $showBeforeDate = null)
	{
		if($showBeforeDate === null || ereg('^[0-9]{4}-[0-9]{2}-[0-9]{2}$', $showBeforeDate))
		{
			if($youthTeamId === null)
			{
				$youthTeamId = $this->getTeam()->getYouthTeamId();
			}
			if(!isset($this->youthTeamsMatches[$youthTeamId][strtotime($showBeforeDate)]) || $this->youthTeamsMatches[$youthTeamId][strtotime($showBeforeDate)] === null)
			{
				$url = $this->buildUrl(self::CHPP_URL, array('file'=>'matches', 'teamID'=>$youthTeamId, 'isYouth'=>'true', 'LastMatchDate'=>$showBeforeDate, 'version'=>'2.3'));
				$this->youthTeamsMatches[$youthTeamId][strtotime($showBeforeDate)] = new HTTeamMatches($this->fetchUrl($url));
			}
			return $this->youthTeamsMatches[$youthTeamId][strtotime($showBeforeDate)];
		}
		return null;
	}

	/**
	 * Delete cache matches of youth team
	 *
	 * @param Integer $youthTeamId
	 */
	public function clearYouthTeamMatches($youthTeamId = null)
	{
		if($youthTeamId === null)
		{
			$youthTeamId = $this->getTeam()->getYouthTeamId();
		}
		$this->youthTeamsMatches[$youthTeamId] = null;
	}

	/**
	 * Delete all caches of youth teams matches
	 */
	public function clearYouthTeamsMatches()
	{
		$this->youthTeamsMatches = array();
	}

	/**
	 * Return HTMatch object
	 *
	 * @param Integer $matchId
	 * @return HTMatch
	 */
	public function getSeniorMatchDetails($matchId)
	{
		if(!isset($this->matchesDetails[$matchId]) || $this->matchesDetails[$matchId] === null)
		{
			$url = $this->buildUrl(self::CHPP_URL, array('file'=>'matchdetails', 'matchEvents'=>'true', 'version'=>'1.8', 'matchID'=>$matchId));
			$this->matchesDetails[$matchId] = new HTMatch($this->fetchUrl($url));
		}
		return $this->matchesDetails[$matchId];
	}

	/**
	 * Delete cache of match detail
	 *
	 * @param Integer $matchId
	 */
	public function clearSeniorMatchDetail($matchId)
	{
		$this->matchesDetails[$matchId] = null;
	}

	/**
	 * Delete cache of all matches details
	 */
	public function clearSeniorMatchesDetails()
	{
		$this->matchesDetails = array();
	}

	/**
	 * Return HTMatch object
	 *
	 * @param Integer $matchId
	 * @return HTMatch
	 */
	public function getYouthMatchDetails($matchId)
	{
		if(!isset($this->youthMatchesDetails[$matchId]) || $this->youthMatchesDetails[$matchId] === null)
		{
			$url = $this->buildUrl(self::CHPP_URL, array('file'=>'matchdetails', 'matchEvents'=>'true', 'version'=>'1.8', 'matchID'=>$matchId, 'isYouth'=>'true'));
			$this->youthMatchesDetails[$matchId] = new HTMatch($this->fetchUrl($url));
		}
		return $this->youthMatchesDetails[$matchId];
	}

	/**
	 * Delete cache of youth match detail
	 *
	 * @param Integer $matchId
	 */
	public function clearYouthMatchDetail($matchId)
	{
		$this->youthMatchesDetails[$matchId] = null;
	}

	/**
	 * Delete cache of all youth matches details
	 */
	public function clearYouthMatchesDetails()
	{
		$this->youthMatchesDetails = array();
	}

	/**
	 * Return HTMatchArchive object
	 *
	 * @param Integer $teamId
	 * @param String $startDate (format should be : yyyy-mm-dd)
	 * @param String $endDate (format should be : yyyy-mm-dd)
	 * @param Integer $season
	 * @return HTMatchArchive
	 */
	public function getSeniorTeamArchiveMatches($teamId = null, $startDate = null, $endDate = null, $season = null)
	{
		if($startDate === null || ereg('^[0-9]{4}-[0-9]{2}-[0-9]{2}$', $startDate))
		{
			if($endDate === null || ereg('^[0-9]{4}-[0-9]{2}-[0-9]{2}$', $endDate))
			{
				$this->analyseDate($startDate, $endDate);
				if($teamId === null)
				{
					$teamId = $this->getClub()->getTeamId();
				}
				if(!isset($this->teamsArchivesMatches[$teamId][strtotime($startDate)][strtotime($endDate)][$season]) || $this->teamsArchivesMatches[$teamId][strtotime($startDate)][strtotime($endDate)][$season] === null)
				{
					$url = $this->buildUrl(self::CHPP_URL, array('file'=>'matchesarchive', 'teamID'=>$teamId, 'isYouth'=>'false', 'FirstMatchDate'=>$startDate, 'LastMatchDate'=>$endDate, 'season'=>$season, 'version'=>'1.1'));
					$this->teamsArchivesMatches[$teamId][strtotime($startDate)][strtotime($endDate)][$season] = new HTMatchArchive($this->fetchUrl($url));
				}
				return $this->teamsArchivesMatches[$teamId][strtotime($startDate)][strtotime($endDate)][$season];
			}
		}
		return null;
	}

	/**
	 * Delete cache of team's archive matches
	 *
	 * @param Integer $teamId
	 */
	public function clearSeniorTeamArchiveMatches($teamId = null)
	{
		if($teamId === null)
		{
			$teamId = $this->getClub()->getTeamId();
		}
		$this->teamsArchivesMatches[$teamId] = null;
	}

	/**
	 * Delete all caches of teams archive matches
	 */
	public function clearSeniorTeamsArchiveMatches()
	{
		$this->teamsArchivesMatches = array();
	}

	/**
	 *  Return HTMatchArchive object
	 *
	 * @param Integer $youthTeamId
	 * @param String $startDate (format should be : yyyy-mm-dd)
	 * @param String $endDate (format should be : yyyy-mm-dd)
	 * @return HTMatchArchive
	 */
	public function getYouthTeamArchiveMatches($youthTeamId = null, $startDate = null, $endDate = null)
	{
		if($startDate === null || ereg('^[0-9]{4}-[0-9]{2}-[0-9]{2}$', $startDate))
		{
			if($endDate === null || ereg('^[0-9]{4}-[0-9]{2}-[0-9]{2}$', $endDate))
			{
				$this->analyseDate($startDate, $endDate);
				if($youthTeamId === null)
				{
					$youthTeamId = $this->getTeam()->getYouthTeamId();
				}
				if(!isset($this->youthTeamsArchivesMatches[$youthTeamId][strtotime($startDate)][strtotime($endDate)]) || $this->youthTeamsArchivesMatches[$youthTeamId][strtotime($startDate)][strtotime($endDate)] === null)
				{
					$url = $this->buildUrl(self::CHPP_URL, array('file'=>'matchesarchive', 'teamID'=>$youthTeamId, 'isYouth'=>'true', 'FirstMatchDate'=>$startDate, 'LastMatchDate'=>$endDate, 'version'=>'1.1'));
					$this->youthTeamsArchivesMatches[$youthTeamId][strtotime($startDate)][strtotime($endDate)] = new HTMatchArchive($this->fetchUrl($url));
				}
				return $this->youthTeamsArchivesMatches[$youthTeamId][strtotime($startDate)][strtotime($endDate)];
			}
		}
		return null;
	}

	/**
	 * Delete cache of youth team's archive matches
	 *
	 * @param Integer $teamId
	 */
	public function clearYouthTeamArchiveMatches($youthTeamId = null)
	{
		if($youthTeamId === null)
		{
			$youthTeamId = $this->getTeam()->getYouthTeamId();
		}
		$this->youthTeamsArchivesMatches[$youthTeamId] = null;
	}

	/**
	 * Delete all caches of youth teams archive matches
	 */
	public function clearYouthTeamsArchiveMatches()
	{
		$this->youthTeamsArchivesMatches = array();
	}

	/**
	 * Return senior HTLineup object
	 *
	 * @param Integer $matchId
	 * @param Integer $teamId
	 * @return HTLineup
	 */
	public function getSeniorLineup($matchId = null, $teamId = null)
	{
		if($teamId === null)
		{
			$teamId = $this->getClub()->getTeamId();
		}
		if($matchId === null)
		{
			$matchId = $this->getSeniorTeamMatches()->getLastMatch()->getId();
		}
		if(!isset($this->lineups[$matchId][$teamId]) || $this->lineups[$matchId][$teamId] === null)
		{
			$url = $this->buildUrl(self::CHPP_URL, array('file'=>'matchlineup', 'version'=>'1.3', 'matchID'=>$matchId, 'teamID'=>$teamId));
			$this->lineups[$matchId][$teamId] = new HTLineup($this->fetchUrl($url));
		}
		return $this->lineups[$matchId][$teamId];
	}

	/**
	 * Delete cache of senior lineup
	 *
	 * @param Integer $matchId
	 * @param Integer $teamId
	 */
	public function clearSeniorLineup($matchId = null, $teamId = null)
	{
		if($teamId === null)
		{
			$teamId = $this->getClub()->getTeamId();
		}
		if($matchId === null)
		{
			$matchId = $this->getSeniorTeamMatches()->getLastMatch()->getId();
		}
		$this->lineups[$matchId][$teamId] = null;
	}

	/**
	 * Delete all caches of senior lineups
	 */
	public function clearSeniorLineups()
	{
		$this->lineups = array();
	}

	/**
	 * Return youth HTLineup object
	 *
	 * @param Integer $matchId
	 * @param Integer $youthTeamId
	 * @return HTLineup
	 */
	public function getYouthLineup($matchId = null, $youthTeamId = null)
	{
		if($youthTeamId === null)
		{
			$youthTeamId = $this->getTeam()->getYouthTeamId();
		}
		if($matchId === null)
		{
			$matchId = $this->getYouthTeamMatches()->getLastMatch()->getId();
		}
		if(!isset($this->youthLineups[$matchId][$youthTeamId]) || $this->youthLineups[$matchId][$youthTeamId] === null)
		{
			$url = $this->buildUrl(self::CHPP_URL, array('file'=>'matchlineup', 'version'=>'1.3', 'matchID'=>$matchId, 'teamID'=>$youthTeamId, 'isYouth'=>'true'));
			$this->youthLineups[$matchId][$youthTeamId] = new HTLineup($this->fetchUrl($url));
		}
		return $this->youthLineups[$matchId][$youthTeamId];
	}

	/**
	 * Delete cache of youth lineup
	 *
	 * @param Integer $matchId
	 * @param Integer $youthTeamId
	 */
	public function clearYouthLineup($matchId = null, $youthTeamId = null)
	{
		if($youthTeamId === null)
		{
			$youthTeamId = $this->getTeam()->getYouthTeamId();
		}
		if($matchId === null)
		{
			$matchId = $this->getSeniorTeamMatches()->getLastMatch()->getId();
		}
		$this->youthLineups[$matchId][$youthTeamId] = null;
	}

	/**
	 * Delete all caches of youth lineups
	 */
	public function clearYouthLineups()
	{
		$this->youthLineups = array();
	}

	/**
	 * Add a match to HT-Live
	 *
	 * @param Integer $matchId
	 */
	public function addLiveMatch($matchId, $isYouth = false)
	{
		$this->checkInitLive();
		if($this->liveNumber < 20)
		{
			$url = $this->buildUrl(self::CHPP_URL, array('file'=>'live', 'actionType'=>'addMatch', 'matchID'=>$matchId, 'version'=>'1.4', 'isYouth'=>$isYouth));
			$this->fetchUrl($url);
			$this->liveNumber = $this->getLive()->getMatchNumber();
		}
	}

	/**
	 * Delete a match in HT-Live
	 *
	 * @param Integer $matchId
	 */
	public function deleteLiveMatch($matchId, $isYouth = false)
	{
		$this->checkInitLive();
		$url = $this->buildUrl(self::CHPP_URL, array('file'=>'live', 'actionType'=>'deleteMatch', 'matchID'=>$matchId, 'version'=>'1.4', 'isYouth'=>$isYouth));
		$this->fetchUrl($url);
		$this->liveNumber = $this->getLive()->getMatchNumber();
	}

	/**
	 * Clear all matches in HT-Live, you have to init live after this method to re-use live
	 */
	public function clearAllLiveMatches()
	{
		$url = $this->buildUrl(self::CHPP_URL, array('file'=>'live', 'actionType'=>'clearAll', 'version'=>'1.4'));
		$this->fetchUrl($url);
		$this->liveNumber = null;
	}

	/**
	 * Return number of match in HT-Live
	 *
	 * @return Integer
	 */
	public function getLiveMatchNumber()
	{
		$this->checkInitLive();
		return $this->liveNumber;
	}

	/**
	 * Return HTLive object
	 *
	 * @param Boolean $forceAllEvents
	 * @return HTLive
	 */
	public function getLive($forceAllEvents = false)
	{
		$action = 'viewNew';
		if($this->liveAllEvents == false || $forceAllEvents == true)
		{
			$action = 'viewAll';
			$this->liveAllEvents = true;
		}
		$url = $this->buildUrl(self::CHPP_URL, array('file'=>'live', 'actionType'=>$action, 'version'=>'1.4'));
		$liveObject = new HTLive($this->fetchUrl($url));
		if(!isset($this->liveNumber) || $this->liveNumber === null)
		{
			$this->liveNumber = $liveObject->getMatchNumber();
		}
		return $liveObject;
	}

	/**
	 * Return A HTNationalTeams object
	 *
	 * @return HTNationalTeams
	 */
	public function getATeamList()
	{
		if(!isset($this->nationalAteams) || $this->nationalAteams === null)
		{
			$url = $this->buildUrl(self::CHPP_URL, array('file'=>'nationalteams', 'LeagueOfficeTypeID'=>2, 'version'=>'1.3'));
			$this->nationalAteams = new HTNationalTeams($this->fetchUrl($url));
		}
		return $this->nationalAteams;
	}

	/**
	 * Delete cache of national teams data
	 */
	public function clearATeamList()
	{
		$this->nationalAteams = null;
	}

	/**
	 * Return U20 HTNationalTeams object
	 *
	 * @return HTNationalTeams
	 */
	public function getU20TeamList()
	{
		if(!isset($this->nationalU20teams) || $this->nationalU20teams === null)
		{
			$url = $this->buildUrl(self::CHPP_URL, array('file'=>'nationalteams', 'LeagueOfficeTypeID'=>4, 'version'=>'1.3'));
			$this->nationalU20teams = new HTNationalTeams($this->fetchUrl($url));
		}
		return $this->nationalU20teams;
	}

	/**
	 * Delete cache of national U20 teams data
	 */
	public function clearU20TeamList()
	{
		$this->nationalU20teams = null;
	}

	/**
	 * Return HTNationalTeamDetail object
	 *
	 * @param Integer $teamId
	 * @return HTNationalTeamDetail
	 */
	public function getNationalTeamDetail($teamId)
	{
		if(!isset($this->nationalTeamDetails[$teamId]) || $this->nationalTeamDetails[$teamId] === null)
		{
			$url = $this->buildUrl(self::CHPP_URL, array('file'=>'nationalteamdetails', 'teamID'=>$teamId, 'version'=>'1.3'));
			$this->nationalTeamDetails[$teamId] = new HTNationalTeamDetail($this->fetchUrl($url));
		}
		return $this->nationalTeamDetails[$teamId];
	}

	/**
	 * Delete cache of national team details
	 *
	 * @param Integer $teamId
	 */
	public function clearNationalTeamDetails($teamId)
	{
		$this->nationalTeamDetails[$teamId] = null;
	}

	/**
	 * Delete all caches of national teams details
	 */
	public function clearNationalTeamsDetails()
	{
		$this->nationalTeamDetails = array();
	}

	/**
	 * Return HTNationalMatches object
	 *
	 * @return HTNationalMatches
	 */
	public function getAMatchesList()
	{
		if(!isset($this->nationalAmatches) || $this->nationalAmatches === null)
		{
			$url = $this->buildUrl(self::CHPP_URL, array('file'=>'nationalteammatches', 'LeagueOfficeTypeID'=>2, 'version'=>'1.2'));
			$this->nationalAmatches = new HTNationalMatches($this->fetchUrl($url));
		}
		return $this->nationalAmatches;
	}

	/**
	 * Delete cache of national A matches
	 */
	public function clearAMatchesList()
	{
		$this->nationalAmatches = null;
	}

	/**
	 * Return HTNationalMatches object
	 *
	 * @return HTNationalMatches
	 */
	public function getU20MatchesList()
	{
		if(!isset($this->nationalU20matches) || $this->nationalU20matches === null)
		{
			$url = $this->buildUrl(self::CHPP_URL, array('file'=>'nationalteammatches', 'LeagueOfficeTypeID'=>4, 'version'=>'1.2'));
			$this->nationalU20matches = new HTNationalMatches($this->fetchUrl($url));
		}
		return $this->nationalU20matches;
	}

	/**
	 * Delete cache of national U20 matches
	 */
	public function clearU20MatchesList()
	{
		$this->nationalU20matches = null;
	}

	/**
	 * Return HTNationalPlayers object
	 *
	 * @param Integer $teamId
	 * @return HTNationalPlayers
	 */
	public function getNationalPlayers($teamId)
	{
		if(!isset($this->nationalPlayers[$teamId]) || $this->nationalPlayers[$teamId] === null)
		{
			$url = $this->buildUrl(self::CHPP_URL, array('file'=>'nationalplayers', 'actionType'=>'view', 'teamID'=>$teamId, 'version'=>'1.2'));
			$this->nationalPlayers[$teamId] = new HTNationalPlayers($this->fetchUrl($url));
		}
		return $this->nationalPlayers[$teamId];
	}

	/**
	 * Delete cache of national players in a team
	 *
	 * @param Integer $teamId
	 */
	public function clearNationalPlayers($teamId)
	{
		$this->nationalPlayers[$teamId] = null;
	}

	/**
	 * Delete all caches of national players
	 */
	public function clearAllNationalPlayers()
	{
		$this->nationalPlayers = array();
	}

	/**
	 * Return HTNationalPlayersStats object
	 *
	 * @param Integer $teamId
	 * @param Boolean $showAll
	 * @return HTNationalPlayersStats
	 */
	public function getNationalPlayersStats($teamId, $showAll = true)
	{
		if(!isset($this->nationalPlayersStats[$teamId][$showAll]) || $this->nationalPlayersStats[$teamId][$showAll] === null)
		{
			$url = $this->buildUrl(self::CHPP_URL, array('file'=>'nationalplayers', 'actionType'=>'supporterStats', 'teamID'=>$teamId, 'matchTypeCategory'=>'NT', 'showAll'=>$showAll, 'version'=>'1.2'));
			$this->nationalPlayersStats[$teamId][$showAll] = new HTNationalPlayersStats($this->fetchUrl($url));
		}
		return $this->nationalPlayersStats[$teamId][$showAll];
	}

	/**
	 * Delete cache of national players stats in a team
	 *
	 * @param Integer $teamId
	 */
	public function clearNationalPlayersStats($teamId)
	{
		$this->nationalPlayersStats[$teamId] = null;
	}

	/**
	 * Delete all caches of national players
	 */
	public function clearAllNationalPlayersStats()
	{
		$this->nationalPlayersStats = array();
	}

	/**
	 * Return HTNationalPlayersStats object
	 *
	 * @param Integer $teamId
	 * @param Boolean $showAll
	 * @return HTNationalPlayersStats
	 */
	public function getNationalPlayersStatsWorldCup($teamId, $showAll = true)
	{
		if(!isset($this->nationalPlayersStatsWC[$teamId][$showAll]) || $this->nationalPlayersStatsWC[$teamId][$showAll] === null)
		{
			$url = $this->buildUrl(self::CHPP_URL, array('file'=>'nationalplayers', 'actionType'=>'supporterStats', 'teamID'=>$teamId, 'matchTypeCategory'=>'WC', 'showAll'=>$showAll, 'version'=>'1.2'));
			$this->nationalPlayersStatsWC[$teamId][$showAll] = new HTNationalPlayersStats($this->fetchUrl($url));
		}
		return $this->nationalPlayersStatsWC[$teamId][$showAll];
	}

	/**
	 * Delete cache of national players stats in a team
	 *
	 * @param Integer $teamId
	 */
	public function clearNationalPlayersStatsWorldCup($teamId)
	{
		$this->nationalPlayersStatsWC[$teamId] = null;
	}

	/**
	 * Delete all caches of national players
	 */
	public function clearAllNationalPlayersStatsWorldCup()
	{
		$this->nationalPlayersStatsWC = array();
	}

	/**
	 * Return HTWorldCupGroup object
	 *
	 * @param Integer $cupId
	 * @param Integer $season
	 * @param Integer $matchRound
	 * @return HTWorldCupGroup
	 */
	public function getWorldCupGroup($cupId = null, $season = null, $matchRound = null)
	{
		if(!isset($this->worldCupGroups[$cupId][$season][$matchRound]) || $this->worldCupGroups[$cupId][$season][$matchRound] === null)
		{
			$url = $this->buildUrl(self::CHPP_URL, array('file'=>'worldcup', 'actionType'=>'viewGroups', 'cupID'=>$cupId, 'season'=>$season, 'matchRound'=>$matchRound, 'version'=>'1.1'));
			$this->worldCupGroups[$cupId][$season][$matchRound] = new HTWorldCupGroup($this->fetchUrl($url));
		}
		return $this->worldCupGroups[$cupId][$season][$matchRound];
	}

	/**
	 * Delete cache of world cup group
	 *
	 * @param Integer $cupId
	 * @param Integer $season
	 * @param Integer $matchRound
	 */
	public function clearWorldCupGroup($cupId = null, $season = null, $matchRound = null)
	{
		$this->worldCupGroups[$cupId][$season][$matchRound] = null;
	}

	/**
	 * Delete all caches of world cup groups
	 */
	public function clearWorldCupGroups()
	{
		$this->worldCupGroups = array();
	}

	/**
	 * Return HTWorldCupMatches object
	 *
	 * @param Integer $cupId
	 * @param Integer $season
	 * @param Integer $matchRound
	 * @return HTWorldCupGroup
	 */
	public function getWorldCupMatches($cupSeriesUnitID, $cupId = null, $season = null, $matchRound = null)
	{
		if(!isset($this->worldCupMatches[$cupSeriesUnitID][$cupId][$season][$matchRound]) || $this->worldCupMatches[$cupSeriesUnitID][$cupId][$season][$matchRound] === null)
		{
			$url = $this->buildUrl(self::CHPP_URL, array('file'=>'worldcup', 'actionType'=>'viewMatches', 'cupID'=>$cupId, 'season'=>$season, 'matchRound'=>$matchRound, 'cupSeriesUnitID'=>$cupSeriesUnitID, 'version'=>'1.1'));
			$this->worldCupMatches[$cupSeriesUnitID][$cupId][$season][$matchRound] = new HTWorldCupMatches($this->fetchUrl($url));
		}
		return $this->worldCupMatches[$cupSeriesUnitID][$cupId][$season][$matchRound];
	}

	/**
	 * Delete cache of world cup group
	 *
	 * @param Integer $cupId
	 * @param Integer $season
	 * @param Integer $matchRound
	 */
	public function clearWorldCupMatches($cupSeriesUnitID, $cupId = null, $season = null, $matchRound = null)
	{
		$this->worldCupMatches[$cupSeriesUnitID][$cupId][$season][$matchRound] = null;
	}

	/**
	 * Delete all caches of world cup groups
	 */
	public function clearAllWorldCupMatches()
	{
		$this->worldCupMatches = array();
	}

	/**
	 * Return HTAlliances object
	 *
	 * @param Integer $languageId
	 * @return HTAlliances
	 */
	public function getAlliances($languageId = -1, $pageIndex = 0)
	{
		if(!isset($this->alliancesIds[$languageId][$pageIndex]) || $this->alliancesIds[$languageId][$pageIndex] === null)
		{
			$this->alliancesIds[$languageId][$pageIndex] = $this->getAlliancesObject(array('searchLanguageID'=>$languageId));
		}
		return $this->alliancesIds[$languageId][$pageIndex];
	}

	/**
	 * Return HTAlliances object
	 *
	 * @param String $name
	 * @return HTAlliances
	 */
	public function getAlliancesByName($name, $languageId = -1, $pageIndex = 0)
	{
		if(!isset($this->alliancesName[$name][$languageId][$pageIndex]) || $this->alliancesName[$name][$languageId][$pageIndex] === null)
		{
			$this->alliancesName[$name][$languageId][$pageIndex] = $this->getAlliancesObject(array('searchFor'=>$name, 'searchType'=>1, 'searchLanguageID'=>$languageId, 'pageIndex'=>$pageIndex));
		}
		return $this->alliancesName[$name][$languageId][$pageIndex];
	}

	/**
	 * Return HTAlliances object
	 *
	 * @param String $abbreviation
	 * @return HTAlliances
	 */
	public function getAlliancesByAbbreviation($abbreviation, $languageId = -1, $pageIndex = 0)
	{
		if(!isset($this->alliancesAbbr[$abbreviation][$languageId][$pageIndex]) || $this->alliancesAbbr[$abbreviation][$languageId][$pageIndex] === null)
		{
			$this->alliancesAbbr[$abbreviation][$languageId][$pageIndex] = $this->getAlliancesObject(array('searchFor'=>$abbreviation, 'searchType'=>2, 'searchLanguageID'=>$languageId, 'pageIndex'=>$pageIndex));
		}
		return $this->alliancesAbbr[$abbreviation][$languageId][$pageIndex];
	}

	/**
	 * Return HTAlliances object
	 *
	 * @param String $description
	 * @return HTAlliances
	 */
	public function getAlliancesByDescription($description, $languageId = -1, $pageIndex = 0)
	{
		if(!isset($this->alliancesDesc[$description][$languageId][$pageIndex]) || $this->alliancesDesc[$description][$languageId][$pageIndex] === null)
		{
			$this->alliancesDesc[$description][$languageId][$pageIndex] = $this->getAlliancesObject(array('searchFor'=>$description, 'searchType'=>3, 'searchLanguageID'=>$languageId, 'pageIndex'=>$pageIndex));
		}
		return $this->alliancesDesc[$description][$languageId][$pageIndex];
	}

	/**
	 * @param Array $extraParams
	 * @return HTAlliances
	 */
	private function getAlliancesObject($extraParams = array())
	{
		$params = array_merge(array('file'=>'alliances', 'version'=>'1.2'), $extraParams);
		$url = $this->buildUrl(self::CHPP_URL, $params);
		return new HTAlliances($this->fetchUrl($url));
	}

	/**
	 * Return HTArena object
	 *
	 * @param Integer $arenaId
	 * @return HTArena
	 */
	public function getArenaDetails($arenaId = null)
	{
		if($arenaId === null)
		{
			$arenaId = $this->getTeam()->getArenaId();
		}
		if(!isset($this->arenas[$arenaId]) || $this->arenas[$arenaId] === null)
		{
			$url = $this->buildUrl(self::CHPP_URL , array('file'=>'arenadetails', 'arenaID'=>$arenaId, 'version'=>'1.3'));
			$this->arenas[$arenaId] = new HTArena($this->fetchUrl($url));
		}
		return $this->arenas[$arenaId];
	}

	/**
	 * Delete cache of arena
	 *
	 * @param Integer $arenaId
	 */
	public function clearArenaDetails($arenaId = null)
	{
		if($arenaId === null)
		{
			$arenaId = $this->getTeam()->getArenaId();
		}
		$this->arenas[$arenaId] = null;
	}

	/**
	 * Delete all caches of arenas
	 */
	public function clearArenasDetails()
	{
		$this->arenas = array();
	}

	/**
	 * Return HTArenaMyStats object
	 *
	 * @param Integer $matchType (constant taken from HTArenaMyStats class)
	 * @param String $startDate (format should be : yyyy-mm-dd)
	 * @param String $endDate (format should be : yyyy-mm-dd)
	 * @return HTArenaMyStats
	 */
	public function getMyArenaStats($matchType = HTArenaMyStats::ALL, $startDate= null, $endDate = null)
	{
		if($startDate === null || ereg('^[0-9]{4}-[0-9]{2}-[0-9]{2}$', $startDate))
		{
			if($endDate === null || ereg('^[0-9]{4}-[0-9]{2}-[0-9]{2}$', $endDate))
			{
				$this->analyseDate($startDate, $endDate, false);
				if(!in_array($matchType, array(HTArenaMyStats::ALL, HTArenaMyStats::COMP, HTArenaMyStats::LEAGUE, HTArenaMyStats::FRIENDLY)))
				{
					$matchType = HTArenaMyStats::ALL;
				}
				if(!isset($this->myArenaStats[$matchType][strtotime($startDate)][strtotime($endDate)]) || $this->myArenaStats[$matchType][strtotime($startDate)][strtotime($endDate)] === null)
				{
					$url = $this->buildUrl(self::CHPP_URL , array('file'=>'arenadetails', 'statsType'=>'SupporterStats', 'matchType'=>$matchType, 'firstDate'=>$startDate, 'lastDate'=>$endDate, 'version'=>'1.3'));
					$this->myArenaStats[$matchType][strtotime($startDate)][strtotime($endDate)] = new HTArenaMyStats($this->fetchUrl($url));
				}
				return $this->myArenaStats[$matchType][strtotime($startDate)][strtotime($endDate)];
			}
		}
		return null;
	}

	/**
	 * Delete cache of my arena statistics
	 *
	 * @param unknown_type $matchType (constant taken from HTArenaMyStats class)
	 */
	public function clearMyArenaStats($matchType = HTArenaMyStats::ALL)
	{
		if(!in_array($matchType, array(HTArenaMyStats::ALL, HTArenaMyStats::COMP, HTArenaMyStats::LEAGUE, HTArenaMyStats::FRIENDLY)))
		{
			$matchType = HTArenaMyStats::ALL;
		}
		$this->myArenaStats[$matchType] = null;
	}

	/**
	 * Delete all caches of my arena statistics
	 */
	public function clearMyArenaAllStats()
	{
		$this->myArenaStats = array();
	}

	/**
	 * Return HTArenasStats object
	 *
	 * @param Integer $leagueId (by default 0 = global statistics)
	 * @return HTArenasStats
	 */
	public function getOthersArenasStats($leagueId = 0)
	{
		if(!isset($this->arenasStats[$leagueId]) || $this->arenasStats[$leagueId] === null)
		{
			$url = $this->buildUrl(self::CHPP_URL , array('file'=>'arenadetails', 'statsType'=>'OthersArenas', 'statsLeagueID'=>$leagueId, 'version'=>'1.3'));
			$this->arenasStats[$leagueId] = new HTArenasStats($this->fetchUrl($url));
		}
		return $this->arenasStats[$leagueId];
	}

	/**
	 * Delete cache of arena statistics for a league
	 *
	 * @param unknown_type $leagueId
	 */
	public function clearOthersArenasStats($leagueId = 0)
	{
		$this->arenasStats[$leagueId] = null;
	}

	/**
	 * Delete all caches of arenas statistics
	 */
	public function clearOthersArenasAllStats()
	{
		$this->arenasStats = array();
	}

	/**
	 * Return HTMatchOrders object
	 *
	 * @param Integer $matchId
	 * @return HTMatchOrders
	 */
	public function getMatchOrders($matchId, $isYouth = false)
	{
		if(!isset($this->matchOrders[$isYouth][$matchId]) || $this->matchOrders[$isYouth][$matchId] === null)
		{
			$url = $this->buildUrl(self::CHPP_URL , array('file'=>'matchorders', 'version'=>'1.4', 'matchID'=>$matchId, 'isYouth'=>$isYouth));
			$this->matchOrders[$isYouth][$matchId] = new HTMatchOrders($this->fetchUrl($url));
		}
		return $this->matchOrders[$isYouth][$matchId];
	}

	/**
	 * Delete cache of match orders
	 *
	 * @param Integer $matchId
	 */
	public function clearMatchOrders($matchId)
	{
		$this->matchOrders[$matchId] = null;
	}

	/**
	 * Delete all caches of matches orders
	 */
	public function clearMatchesOrders()
	{
		$this->matchOrders = array();
	}

	/**
	 * Return HTChallanges object
	 *
	 * @return HTChallanges
	 */
	public function getChallanges()
	{
		if(!isset($this->challanges) || $this->challanges === null)
		{
			$url = $this->buildUrl(self::CHPP_URL , array('file'=>'challenges', 'version'=>'1.2'));
			$this->challanges = new HTChallanges($this->fetchUrl($url));
		}
		return $this->challanges;
	}

	/**
	 * Delete cache of challanges
	 */
	public function clearChallanges()
	{
		$this->challanges = null;
	}

	/**
	 * Return HTCup object
	 *
	 * @param Integer $cupId
	 * @param Integer $round
	 * @param Integer $season
	 * @param Integer $afterMatchId
	 * @return HTCup
	 */
	public function getCup($cupId, $round, $season = null, $afterMatchId = null)
	{
		if(!isset($this->cups[$cupId][$round][$season][$afterMatchId]) || $this->cups[$cupId][$round][$season][$afterMatchId] === null)
		{
			$url = $this->buildUrl(self::CHPP_URL, array('file'=>'cupmatches', 'cupID'=>$cupId, 'cupRound'=>$round, 'season'=>$season, 'startAfterMatchId'=>$afterMatchId, 'version'=>'1.2'));
			$this->cups[$cupId][$round][$season][$afterMatchId] = new HTCup($this->fetchUrl($url));
		}
		return $this->cups[$cupId][$round][$season][$afterMatchId];
	}

	/**
	 * Delete cache of a cup
	 *
	 * @param Integer $cupId
	 */
	public function clearCup($cupId)
	{
		$this->cups[$cupId] = null;
	}

	/**
	 * Delete all caches of cups
	 */
	public function clearCups()
	{
		$this->cups = array();
	}

	/**
	 * Return HTSearch object
	 *
	 * @param Integer $teamId
	 * @return HTSearch
	 */
	public function searchTeamById($teamId)
	{
		if(!isset($this->searchTeamId[$teamId]) || $this->searchTeamId[$teamId] === null)
		{
			$params = array('searchType'=>'4', 'searchID'=>$teamId);
			$this->searchTeamId[$teamId] = $this->search($params);
		}
		return $this->searchTeamId[$teamId];
	}

	/**
	 * Delete cache of search team by id
	 *
	 * @param Integer $teamId
	 */
	public function clearSearchTeamById($teamId)
	{
		$this->searchTeamId[$teamId] = null;
	}

	/**
	 * Delete all caches of search team by id
	 */
	public function clearAllSearchTeamById()
	{
		$this->searchTeamId = array();
	}

	/**
	 * Return HTSearch object
	 *
	 * @param String $teamName
	 * @param Integer $pageIndex
	 * @return HTSearch
	 */
	public function searchTeamByName($teamName, $pageIndex = 0)
	{
		if(!isset($this->searchTeamName[$teamName][$pageIndex]) || $this->searchTeamName[$teamName][$pageIndex] === null)
		{
			$params = array('searchType'=>'4', 'searchString'=>urlencode($teamName), 'pageIndex'=>$pageIndex);
			$this->searchTeamName[$teamName][$pageIndex] = $this->search($params);
		}
		return $this->searchTeamName[$teamName][$pageIndex];
	}

	/**
	 * Delete cache of search team by name
	 *
	 * @param String $teamName
	 */
	public function clearSearchTeamByName($teamName)
	{
		$this->searchTeamName[$teamName] = null;
	}

	/**
	 * Delete all caches of search team by name
	 */
	public function clearAllSearchTeamByName()
	{
		$this->searchTeamName = array();
	}

	/**
	 * Return HTSearch object
	 *
	 * @param String $loginName
	 * @param Integer $pageIndex
	 * @return HTSearch
	 */
	public function searchUserByName($name, $pageIndex = 0)
	{
		if(!isset($this->searchUserName[$name][$pageIndex]) || $this->searchUserName[$name][$pageIndex] === null)
		{
			$params = array('searchType'=>'2', 'searchString'=>urlencode($name), 'pageIndex'=>$pageIndex);
			$this->searchUserName[$name][$pageIndex] = $this->search($params);
		}
		return $this->searchUserName[$name][$pageIndex];
	}

	/**
	 * Delete cache of search user by loginname
	 *
	 * @param String $loginName
	 */
	public function clearSearchUserByName($name)
	{
		$this->searchUserName[$name] = null;
	}

	/**
	 * Delete all caches of search user by loginname
	 */
	public function clearAllSearchUserByName()
	{
		$this->searchUserName = array();
	}

	/**
	 * Return HTSearch object
	 *
	 * @param Integer $playerId
	 * @return HTSearch
	 */
	public function searchPlayerById($playerId)
	{
		if(!isset($this->searchPlayerId[$playerId]) || $this->searchPlayerId[$playerId] === null)
		{
			$params = array('searchType'=>'0', 'searchID'=>$playerId);
			$this->searchPlayerId[$playerId] = $this->search($params);
		}
		return $this->searchPlayerId[$playerId];
	}

	/**
	 * Delete cache of search player by id
	 *
	 * @param Integer $playerId
	 */
	public function clearSearchPlayerById($playerId)
	{
		$this->searchPlayerId[$playerId] = null;
	}

	/**
	 * Delete all caches of search player by id
	 */
	public function clearAllSearchPlayerById()
	{
		$this->searchPlayerId = array();
	}

	/**
	 * Return HTSearch object
	 *
	 * @param String $firstName
	 * @param String $lastName
	 * @param Integer $pageIndex
	 * @return HTSearch
	 */
	public function searchPlayerByName($firstName, $lastName, $pageIndex = 0, $leagueId = null)
	{
		if($leagueId === null)
		{
			$leagueId = $this->getTeam()->getLeagueId();
		}
		if(!isset($this->searchPlayerName[$firstName][$lastName][$pageIndex][$leagueId]) || $this->searchPlayerName[$firstName][$lastName][$pageIndex][$leagueId] === null)
		{
			$params = array('searchType'=>'0', 'searchString'=>$firstName, 'searchString2'=>$lastName, 'pageIndex'=>$pageIndex, 'searchLeagueID'=>$leagueId);
			$this->searchPlayerName[$firstName][$lastName][$pageIndex][$leagueId] = $this->search($params);
		}
		return $this->searchPlayerName[$firstName][$lastName][$pageIndex][$leagueId];
	}

	/**
	 * Delete cache of search player by name
	 *
	 * @param String $firstName
	 * @param String $lastName
	 */
	public function clearSearchPlayerByName($firstName, $lastName)
	{
		$this->searchPlayerName[$firstName][$lastName] = null;
	}

	/**
	 * Delete all caches of search player by name
	 */
	public function clearAllSearchPlayerByName()
	{
		$this->searchPlayerName = array();
	}

	/**
	 * Return HTSearch object
	 *
	 * @param String $regionName
	 * @param Integer $pageIndex
	 * @return HTSearch
	 */
	public function searchRegionByName($regionName, $pageIndex = 0)
	{
		if(!isset($this->searchRegionName[$regionName][$pageIndex]) || $this->searchRegionName[$regionName][$pageIndex] === null)
		{
			$params = array('searchType'=>'5', 'searchString'=>$regionName, 'pageIndex'=>$pageIndex);
			$this->searchRegionName[$regionName][$pageIndex] = $this->search($params);
		}
		return $this->searchRegionName[$regionName][$pageIndex];
	}

	/**
	 * Delete cache of search region by name
	 *
	 * @param String $regionName
	 */
	public function clearSearchRegionByName($regionName)
	{
		$this->searchRegionName[$regionName] = array();
	}

	/**
	 * Delete all caches of search region by name
	 */
	public function clearAllSearchRegionByName()
	{
		$this->searchRegionName = null;
	}

	/**
	 * Return HTSearch object
	 *
	 * @param String $regionName
	 * @param Integer $pageIndex
	 * @return HTSearch
	 */
	public function searchArenaByName($arenaName, $pageIndex = 0)
	{
		if(!isset($this->searchArenaName[$arenaName][$pageIndex]) || $this->searchArenaName[$arenaName][$pageIndex] === null)
		{
			$params = array('searchType'=>'1', 'searchString'=>$arenaName, 'pageIndex'=>$pageIndex);
			$this->searchArenaName[$arenaName][$pageIndex] = $this->search($params);
		}
		return $this->searchArenaName[$arenaName][$pageIndex];
	}

	/**
	 * Delete cache of search arena by name
	 *
	 * @param String $arenaName
	 */
	public function clearSearchArenaByName($arenaName)
	{
		$this->searchArenaName[$arenaName] = null;
	}

	/**
	 * Delete all caches of search arena by name
	 */
	public function clearAllSearchArenaByName()
	{
		$this->searchArenaName = array();
	}

	/**
	 * Return HTSearch object
	 *
	 * @param Integer $matchId
	 * @return HTSearch
	 */
	public function searchMatch($matchId)
	{
		if(!isset($this->searchMatchId[$matchId]) || $this->searchMatchId[$matchId] === null)
		{
			$params = array('searchType'=>'6', 'searchID'=>$matchId);
			$this->searchMatchId[$matchId] = $this->search($params);
		}
		return $this->searchMatchId[$matchId];
	}

	/**
	 * Delete cache of search match
	 *
	 * @param String $matchId
	 */
	public function clearSearchMatch($matchId)
	{
		$this->searchMatchId[$matchId] = null;
	}

	/**
	 * Delete all caches of search arena by name
	 */
	public function clearAllSearchMatch()
	{
		$this->searchMatchId = array();
	}

	/**
	 * Return HTSearch object
	 *
	 * @param Integer $serieId
	 * @param Integer $leagueId
	 * @return HTSearch
	 */
	public function searchSerieById($serieId, $leagueId = null)
	{
		if($leagueId === null)
		{
			$leagueId = $this->getTeam()->getLeagueId();
		}
		if(!isset($this->searchSeriesId[$serieId][$leagueId]) || $this->searchSeriesId[$serieId][$leagueId] === null)
		{
			$params = array('searchType'=>'3', 'searchID'=>$serieId, 'searchLeagueID'=>$leagueId);
			$this->searchSeriesId[$serieId][$leagueId] = $this->search($params);
		}
		return $this->searchSeriesId[$serieId][$leagueId];
	}

	/**
	 * Delete cache of search serie by id
	 *
	 * @param Integer $serieId
	 * @param Integer $leagueId
	 */
	public function clearSearchSerieById($serieId, $leagueId = null)
	{
		if($leagueId === null)
		{
			$leagueId = $this->getTeam()->getLeagueId();
		}
		$this->searchSeriesId[$serieId][$leagueId] = null;
	}

	/**
	 * Delete all caches of search serie by id
	 */
	public function clearAllSearchSerieById()
	{
		$this->searchSeriesId = array();
	}

	/**
	 * Return HTSearch object
	 *
	 * @param String $serieName
	 * @param Integer $leagueId
	 * @return HTSearch
	 */
	public function searchSerieByName($serieName, $leagueId = null)
	{
		if($leagueId === null)
		{
			$leagueId = $this->getTeam()->getLeagueId();
		}
		if(!isset($this->searchSeriesName[$serieName][$leagueId]) || $this->searchSeriesName[$serieName][$leagueId] === null)
		{
			$params = array('searchType'=>'3', 'searchString'=>$serieName, 'searchLeagueID'=>$leagueId);
			$this->searchSeriesName[$serieName][$leagueId] = $this->search($params);
		}
		return $this->searchSeriesName[$serieName][$leagueId];
	}

	/**
	 * Delete cache of search serie by name
	 *
	 * @param String $serieName
	 * @param Integer $leagueId
	 */
	public function clearSearchSerieByName($serieName, $leagueId = null)
	{
		if($leagueId === null)
		{
			$leagueId = $this->getTeam()->getLeagueId();
		}
		$this->searchSeriesName[$serieName][$leagueId] = null;
	}

	/**
	 * Delete all caches of search serie by id
	 */
	public function clearAllSearchSerieByName()
	{
		$this->searchSeriesName = array();
	}

	/**
	 * @param Array $extraParams
	 * @return HTSearch
	 */
	private function search($extraParams)
	{
		$params = array('file'=>'search', 'version'=>'1.2');
		$params = array_merge($params, $extraParams);
		$url = $this->buildUrl(self::CHPP_URL, $params);
		return new HTSearch($this->fetchUrl($url));
	}

	/**
	 * Return HTBookmarks object
	 *
	 * @param Integer $type
	 * @return HTBookmarks
	 */
	public function getBookmarks($type = HTBookmarksGlobal::ALL)
	{
		$type = round($type);
		if($type < HTBookmarksGlobal::ALL || $type > HTBookmarksGlobal::CONFTHREADS)
		{
			$type = HTBookmarksGlobal::ALL;
		}
		if(!isset($this->bookmarks[$type]) || $this->bookmarks[$type] === null)
		{
			$url = $this->buildUrl(self::CHPP_URL, array('file'=>'bookmarks', 'BookmarkTypeID'=>$type, 'version'=>'1.0'));
			$this->bookmarks[$type] = new HTBookmarks($this->fetchUrl($url));
		}
		return $this->bookmarks[$type];
	}

	/**
	 * Delete cache of bookmarks for given type
	 *
	 * @param Integer $type
	 */
	public function clearBookmarks($type = HTBookmarksGlobal::ALL)
	{
		$type = round($type);
		if($type < HTBookmarksGlobal::ALL || $type > HTBookmarksGlobal::CONFTHREADS)
		{
			$type = HTBookmarksGlobal::ALL;
		}
		$this->bookmarks[$type] = null;
	}

	/**
	 * Delete all cache of bookmarks
	 */
	public function clearAllBookmarks()
	{
		$this->bookmarks = array();
	}

	/**
	 * Return HTBookmarksTeams object
	 *
	 * @return HTBookmarksTeams
	 */
	public function getBookmarksTeams()
	{
		if(!isset($this->bookmarksTeams) || $this->bookmarksTeams === null)
		{
			$url = $this->buildUrl(self::CHPP_URL, array('file'=>'bookmarks', 'BookmarkTypeID'=>'1', 'version'=>'1.0'));
			$this->bookmarksTeams = new HTBookmarksTeams($this->fetchUrl($url));
		}
		return $this->bookmarksTeams;
	}

	/**
	 * Delete cache of bookmarks teams
	 */
	public function clearBookmarksTeams()
	{
		$this->bookmarksTeams = null;
	}

	/**
	 * Return HTBookmarksPlayers object
	 *
	 * @return HTBookmarksPlayers
	 */
	public function getBookmarksPlayers()
	{
		if(!isset($this->bookmarksPlayers) || $this->bookmarksPlayers === null)
		{
			$url = $this->buildUrl(self::CHPP_URL, array('file'=>'bookmarks', 'BookmarkTypeID'=>'2', 'version'=>'1.0'));
			$this->bookmarksPlayers = new HTBookmarksPlayers($this->fetchUrl($url));
		}
		return $this->bookmarksPlayers;
	}

	/**
	 * Delete cache of bookmarks players
	 */
	public function clearBookmarksPlayers()
	{
		$this->bookmarksPlayers = null;
	}

	/**
	 * Return HTBookmarksMatches object
	 *
	 * @return HTBookmarksMatches
	 */
	public function getBookmarksMatches()
	{
		if(!isset($this->bookmarksMatches) || $this->bookmarksMatches === null)
		{
			$url = $this->buildUrl(self::CHPP_URL, array('file'=>'bookmarks', 'BookmarkTypeID'=>'3', 'version'=>'1.0'));
			$this->bookmarksMatches = new HTBookmarksMatches($this->fetchUrl($url));
		}
		return $this->bookmarksMatches;
	}

	/**
	 * Delete cache of bookmarks matches
	 */
	public function clearBookmarksMatches()
	{
		$this->bookmarksMatches = null;
	}

	/**
	 * Return HTBookmarksConfUsers object
	 *
	 * @return HTBookmarksConfUsers
	 */
	public function getBookmarksConfUsers()
	{
		if(!isset($this->bookmarksConfUsers) || $this->bookmarksConfUsers === null)
		{
			$url = $this->buildUrl(self::CHPP_URL, array('file'=>'bookmarks', 'BookmarkTypeID'=>'4', 'version'=>'1.0'));
			$this->bookmarksConfUsers = new HTBookmarksConfUsers($this->fetchUrl($url));
		}
		return $this->bookmarksConfUsers;
	}

	/**
	 * Delete cache of bookmarks conference users
	 */
	public function clearBookmarksConfUsers()
	{
		$this->bookmarksConfUsers = null;
	}

	/**
	 * Return HTBookmarksLeagues object
	 *
	 * @return HTBookmarksLeagues
	 */
	public function getBookmarksLeagues()
	{
		if(!isset($this->bookmarksLeagues) || $this->bookmarksLeagues === null)
		{
			$url = $this->buildUrl(self::CHPP_URL, array('file'=>'bookmarks', 'BookmarkTypeID'=>'5', 'version'=>'1.0'));
			$this->bookmarksLeagues = new HTBookmarksLeagues($this->fetchUrl($url));
		}
		return $this->bookmarksLeagues;
	}

	/**
	 * Delete cache of bookmarks leagues
	 */
	public function clearBookmarksLeagues()
	{
		$this->bookmarksLeagues = null;
	}

	/**
	 * Return HTBookmarksYouthTeams object
	 *
	 * @return HTBookmarksYouthTeams
	 */
	public function getBookmarksYouthTeams()
	{
		if(!isset($this->bookmarksYouthTeams) || $this->bookmarksYouthTeams === null)
		{
			$url = $this->buildUrl(self::CHPP_URL, array('file'=>'bookmarks', 'BookmarkTypeID'=>'6', 'version'=>'1.0'));
			$this->bookmarksYouthTeams = new HTBookmarksYouthTeams($this->fetchUrl($url));
		}
		return $this->bookmarksYouthTeams;
	}

	/**
	 * Delete cache of bookmarks youth teams
	 */
	public function clearBookmarksYouthTeams()
	{
		$this->bookmarksYouthTeams = null;
	}

	/**
	 * Return HTBookmarksYouthPlayers object
	 *
	 * @return HTBookmarksYouthPlayers
	 */
	public function getBookmarksYouthPlayers()
	{
		if(!isset($this->bookmarksYouthPlayers) || $this->bookmarksYouthPlayers === null)
		{
			$url = $this->buildUrl(self::CHPP_URL, array('file'=>'bookmarks', 'BookmarkTypeID'=>'7', 'version'=>'1.0'));
			$this->bookmarksYouthPlayers = new HTBookmarksYouthPlayers($this->fetchUrl($url));
		}
		return $this->bookmarksYouthPlayers;
	}

	/**
	 * Delete cache of bookmarks youth players
	 */
	public function clearBookmarksYouthPlayers()
	{
		$this->bookmarksYouthPlayers = null;
	}

	/**
	 * Return HTBookmarksYouthMatches object
	 *
	 * @return HTBookmarksYouthMatches
	 */
	public function getBookmarksYouthMatches()
	{
		if(!isset($this->bookmarksYouthMatches) || $this->bookmarksYouthMatches === null)
		{
			$url = $this->buildUrl(self::CHPP_URL, array('file'=>'bookmarks', 'BookmarkTypeID'=>'8', 'version'=>'1.0'));
			$this->bookmarksYouthMatches = new HTBookmarksYouthMatches($this->fetchUrl($url));
		}
		return $this->bookmarksYouthMatches;
	}

	/**
	 * Delete cache of bookmarks youth matches
	 */
	public function clearBookmarksYouthMatches()
	{
		$this->bookmarksYouthMatches = null;
	}

	/**
	 * Return HTBookmarksYouthLeagues object
	 *
	 * @return HTBookmarksYouthLeagues
	 */
	public function getBookmarksYouthLeagues()
	{
		if(!isset($this->bookmarksYouthLeagues) || $this->bookmarksYouthLeagues === null)
		{
			$url = $this->buildUrl(self::CHPP_URL, array('file'=>'bookmarks', 'BookmarkTypeID'=>'9', 'version'=>'1.0'));
			$this->bookmarksYouthLeagues = new HTBookmarksYouthLeagues($this->fetchUrl($url));
		}
		return $this->bookmarksYouthLeagues;
	}

	/**
	 * Delete cache of bookmarks youth leagues
	 */
	public function clearBookmarksYouthLeagues()
	{
		$this->bookmarksYouthLeagues = null;
	}

	/**
	 * Return HTBookmarksConfPosts object
	 *
	 * @return HTBookmarksConfPosts
	 */
	public function getBookmarksConfPosts()
	{
		if(!isset($this->bookmarksConfPosts) || $this->bookmarksConfPosts === null)
		{
			$url = $this->buildUrl(self::CHPP_URL, array('file'=>'bookmarks', 'BookmarkTypeID'=>'10', 'version'=>'1.0'));
			$this->bookmarksConfPosts = new HTBookmarksConfPosts($this->fetchUrl($url));
		}
		return $this->bookmarksConfPosts;
	}

	/**
	 * Delete cache of bookmarks conference posts
	 */
	public function clearBookmarksConfPosts()
	{
		$this->bookmarksConfPosts = null;
	}

	/**
	 * Return HTBookmarksConfThreads object
	 *
	 * @return HTBookmarksConfThreads
	 */
	public function getBookmarksConfThreads()
	{
		if(!isset($this->bookmarksConfThreads) || $this->bookmarksConfThreads === null)
		{
			$url = $this->buildUrl(self::CHPP_URL, array('file'=>'bookmarks', 'BookmarkTypeID'=>'11', 'version'=>'1.0'));
			$this->bookmarksConfThreads = new HTBookmarksConfThreads($this->fetchUrl($url));
		}
		return $this->bookmarksConfThreads;
	}

	/**
	 * Delete cache of bookmarks conference threads
	 */
	public function clearBookmarksConfThreads()
	{
		$this->bookmarksConfThreads = null;
	}

	/**
	 * Return HTTrainingEvents object
	 *
	 * @param Integer $playerId
	 * @return HTTrainingEvents
	 */
	public function getTrainingEvents($playerId)
	{
		if(!isset($this->trainingEvents[$playerId]) || $this->trainingEvents[$playerId] === null)
		{
			$url = $this->buildUrl(self::CHPP_URL, array('file'=>'trainingevents', 'playerID'=>$playerId, 'version'=>'1.2'));
			$this->trainingEvents[$playerId] = new HTTrainingEvents($this->fetchUrl($url));
		}
		return $this->trainingEvents[$playerId];
	}

	/**
	 * Delete cache of training events for a player
	 *
	 * @param Integer $playerId
	 */
	public function clearTrainingEvents($playerId)
	{
		$this->trainingEvents[$playerId] = null;
	}

	/**
	 * Delete all cache of training events
	 */
	public function clearTrainingsEvents()
	{
		$this->trainingEvents = array();
	}

	/**
	 * Return HTPlayerEvents object
	 *
	 * @param Integer $playerId
	 * @param Integer $languageId
	 * @return HTPlayerEvents
	 */
	public function getPlayerEvents($playerId)
	{
		if(!isset($this->playerEvents[$playerId]) || $this->playerEvents[$playerId] === null)
		{
			$url = $this->buildUrl(self::CHPP_URL, array('file'=>'playerevents', 'playerID'=>$playerId, 'version'=>'1.2'));
			$this->playerEvents[$playerId] = new HTPlayerEvents($this->fetchUrl($url));
		}
		return $this->playerEvents[$playerId];
	}

	/**
	 * Delete cache of player events
	 *
	 * @param Integer $playerId
	 */
	public function clearPlayerEvents($playerId)
	{
		$this->playerEvents[$playerId] = null;
	}

	/**
	 * Delete all cache of players events
	 */
	public function clearPlayersEvents()
	{
		$this->playerEvents = array();
	}

	/**
	 * Return HTAllianceDetails object
	 *
	 * @param Integer $allianceId
	 * @return HTAllianceDetails
	 */
	public function getAllianceDetails($allianceId)
	{
		if(!isset($this->allianceDetails[$allianceId]) || $this->allianceDetails[$allianceId] === null)
		{
			$url = $this->buildUrl(self::CHPP_URL , array('file'=>'allianceDetails', 'allianceID'=>$allianceId, 'showRules'=>'true', 'showLoggedIn'=>'true', 'version'=>'1.3'));
			$this->allianceDetails[$allianceId] = new HTAllianceDetails($this->fetchUrl($url));
		}
		return $this->allianceDetails[$allianceId];
	}

	/**
	 * Delete cache of alliance details
	 *
	 * @param Integer $allianceId
	 */
	public function clearAllianceDetails($allianceId)
	{
		$this->allianceDetails[$allianceId] = null;
	}

	/**
	 * Delete all cache of alliances details
	 */
	public function clearAlliancesDetails()
	{
		$this->allianceDetails = array();
	}

	/**
	 * Return HTAllianceMembers object
	 *
	 * @param Integer $allianceId
	 * @return HTAllianceMembers
	 */
	public function getAllianceMembers($allianceId)
	{
		if(!isset($this->allianceMembers[$allianceId]) || $this->allianceMembers[$allianceId] === null)
		{
			$url = $this->buildUrl(self::CHPP_URL , array('file'=>'allianceDetails', 'actionType'=>'members', 'allianceID'=>$allianceId, 'version'=>'1.3'));
			$this->allianceMembers[$allianceId] = new HTAllianceMembers($this->fetchUrl($url));
		}
		return $this->allianceMembers[$allianceId];
	}

	/**
	 * Delete cache of alliance members
	 *
	 * @param Integer $allianceId
	 */
	public function clearAllianceMembers($allianceId)
	{
		$this->allianceMembers[$allianceId] = null;
	}

	/**
	 * Delete all cache of alliances members
	 */
	public function clearAlliancesMembers()
	{
		$this->allianceMembers = array();
	}

	/**
	 * Return HTAllianceMembers object
	 *
	 * @param Integer $allianceId
	 * @param String $letter
	 * @return HTAllianceMembers
	 */
	public function getAllianceMembersByLetter($allianceId, $letter)
	{
		$letter = ord(strtoupper($letter));
		if($letter < 65 || $letter > 90)
		{
			$letter = 0;
		}
		if(!isset($this->allianceMembersLetters[$allianceId][$letter]) || $this->allianceMembersLetters[$allianceId][$letter] === null)
		{
			$url = $this->buildUrl(self::CHPP_URL , array('file'=>'allianceDetails', 'actionType'=>'membersSubset', 'allianceID'=>$allianceId, 'subSet'=>$letter, 'version'=>'1.3'));
			$this->allianceMembersLetters[$allianceId][$letter] = new HTAllianceMembers($this->fetchUrl($url));
		}
		return $this->allianceMembersLetters[$allianceId][$letter];
	}

	/**
	 * Delete cache of alliance members by letter
	 *
	 * @param Integer $allianceId
	 */
	public function clearAllianceMembersByLetter($allianceId, $letter)
	{
		$letter = ord(strtoupper($letter));
		if($letter < 65 || $letter > 90)
		{
			$letter = 0;
		}
		$this->allianceMembersLetters[$allianceId][$letter] = null;
	}

	/**
	 * Delete cache of alliance members by letters
	 *
	 * @param Integer $allianceId
	 */
	public function clearAllianceMembersByLetters($allianceId)
	{
		$this->allianceMembersLetters[$allianceId] = null;
	}

	/**
	 * Delete all cache of alliances by letters
	 */
	public function clearAlliancesMembersByLetters()
	{
		$this->allianceMembersLetters = array();
	}

	/**
	 * Return HTAllianceRoles object
	 *
	 * @param Integer $allianceId
	 * @return HTAllianceMembers
	 */
	public function getAllianceRoles($allianceId)
	{
		if(!isset($this->allianceRoles[$allianceId]) || $this->allianceRoles[$allianceId] === null)
		{
			$url = $this->buildUrl(self::CHPP_URL , array('file'=>'allianceDetails', 'actionType'=>'roles', 'allianceID'=>$allianceId, 'version'=>'1.3'));
			$this->allianceRoles[$allianceId] = new HTAllianceRoles($this->fetchUrl($url));
		}
		return $this->allianceRoles[$allianceId];
	}

	/**
	 * Delete cache of alliance roles
	 *
	 * @param Integer $allianceId
	 */
	public function clearAllianceRoles($allianceId)
	{
		$this->allianceRoles[$allianceId] = null;
	}

	/**
	 * Delete all cache of alliances roles
	 */
	public function clearAlliancesRoles()
	{
		$this->allianceRoles = array();
	}

	/**
	 * Return HTAllianceRoleDetails object
	 *
	 * @param Integer $allianceId
	 * @return HTAllianceMembers
	 */
	public function getAllianceRoleDetails($allianceId, $roleId)
	{
		if(!isset($this->allianceRoleDetails[$allianceId][$roleId]) || $this->allianceRoleDetails[$allianceId][$roleId] === null)
		{
			$url = $this->buildUrl(self::CHPP_URL , array('file'=>'allianceDetails', 'actionType'=>'viewRole', 'allianceID'=>$allianceId, 'allianceRoleID'=>$roleId, 'version'=>'1.3'));
			$this->allianceRoleDetails[$allianceId][$roleId] = new HTAllianceRoleDetails($this->fetchUrl($url));
		}
		return $this->allianceRoleDetails[$allianceId][$roleId];
	}

	/**
	 * Delete cache of alliance role details
	 *
	 * @param Integer $allianceId
	 * @param Integer $roleID
	 */
	public function clearAllianceRoleDetails($allianceId, $roleID)
	{
		$this->allianceRoleDetails[$allianceId][$roleID] = null;
	}

	/**
	 * Delete cache of alliance roles details
	 *
	 * @param Integer $allianceId
	 * @param Integer $roleID
	 */
	public function clearAllianceRolesDetails($allianceId)
	{
		$this->allianceRoleDetails[$allianceId] = null;
	}

	/**
	 * Delete all cache of alliances roles
	 */
	public function clearAlliancesRolesDetails()
	{
		$this->allianceRoleDetails = array();
	}

	/**
	 * Return HTFans object
	 *
	 * @return HTFans
	 */
	public function getFans()
	{
		if(!isset($this->fans) || $this->fans === null)
		{
			$url = $this->buildUrl(self::CHPP_URL, array('file'=>'fans', 'version'=>'1.0'));
			$this->fans = new HTFans($this->fetchUrl($url));
		}
		return $this->fans;
	}

	/**
	 * Delete cache of fans
	 */
	public function clearFans()
	{
		$this->fans = null;
	}

	/**
	 * Return HTAchievements object
	 *
	 * @return HTAchievements
	 */
	public function getAchievements($userId = null)
	{
		if($userId === null)
		{
			$userId = $this->getClub()->getUserId();
		}
		if(!isset($this->achievements[$userId]) || $this->achievements[$userId] === null)
		{
			$url = $this->buildUrl(self::CHPP_URL, array('file'=>'achievements', 'userID'=>$userId, 'version'=>'1.0'));
			$this->achievements[$userId] = new HTAchievements($this->fetchUrl($url));
		}
		return $this->achievements[$userId];
	}

	/**
	 * Delete cache of achievements
	 */
	public function clearAchivements($userId = null)
	{
		if($userId === null)
		{
			$userId = $this->getClub()->getUserId();
		}
		$this->achievements[$userId] = null;
	}

	/**
	 * Delete cache of all achievements
	 */
	public function clearAllAchivements()
	{
		$this->achievements = array();
	}


	/**
	 * Return connected host url
	 *
	 * @return String
	 */
	public function getHost()
	{
		return $this->host;
	}

	/**
	 */
	private function checkInitLive()
	{
		if(!isset($this->liveNumber) || $this->liveNumber === null)
		{
			$this->getLive();
		}
	}

	/**
	 * @param String $startDate
	 * @param String $endDate
	 * @param Boolean $limit
	 */
	private function analyseDate(&$startDate, &$endDate, $limit = true)
	{
		if($endDate === null && $startDate !== null)
		{
			$endDate = date('Y-m-d');
		}
		if($startDate === null && $endDate !== null)
		{
			if($limit == true)
			{
				$startDate = date('Y-m-d', strtotime($endDate)-(3600*24*7*16)); // 1 season
			}
			else
			{
				$startDate = null;
			}
		}
		if($startDate !== null && $endDate !== null)
		{
			if($startDate > $endDate)
			{
				$tmp = $startDate; $startDate = $endDate; $endDate = $tmp;
			}
			if($limit == true)
			{
				$start = strtotime($startDate);
				$end = strtotime($endDate);
				if($end-$start > 3600*24*7*16*2) // 2 seasons
				{
					$startDate = $endDate = null;
				}
			}
		}
	}

	/**
	 * @param String $url
	 * @return String
	 */
	private function fetchUrl($url)
	{
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_USERAGENT, $this->agent);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_COOKIE, implode("; ", $this->cookies));
		curl_setopt($curl, CURLOPT_HEADER, true);
		$response = curl_exec($curl);
		$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
  	$header = substr($response, 0, $header_size);
  	$this->addCookie($header);
    $xmlData = substr($response, $header_size);
		curl_close($curl);
		$this->checkXmlData($xmlData);
		return $xmlData;
	}

	/**
	 * Save cookie when safe mode enabled
	 *
	 * @param String $header
	 */
  private function addCookie($header)
  {
  	$lines = explode("\r\n", $header);
  	foreach ($lines as $line)
  	{
  		if(strpos($line, 'Set-Cookie: ')!==false)
  		{
  			$cookie = substr($line, strlen('Set-Cookie: '));
  			$name = substr($cookie, 0, strpos($cookie, '='));
  			if(in_array($name, $this->getKnownCookiesNames()))
  			{
  				$this->replaceCookie($name, $cookie);
  			}
  			else
  			{
  				$this->cookies[] = $cookie;
  			}
  		}
  	}
  }

  /**
   * Returns names of already known cookies
   *
   * @return Array
   */
  private function getKnownCookiesNames()
  {
  	$known = array();
  	foreach ($this->cookies as $cookie)
  	{
  		$known[] = substr($cookie, 0, strpos($cookie, '='));
  	}
  	return $known;
  }

  /**
   * Replace cookie if it change during transfer
   *
   * @param String $cookieName
   * @param String $newCookie
   */
  private function replaceCookie($cookieName, $newCookie)
  {
  	$correctCookies = array($newCookie);
  	foreach ($this->cookies as $cookie)
  	{
  		if(substr($cookie, 0, strlen($cookieName)) != $cookieName)
  		{
  			$correctCookies[] = $cookie;
  		}
  	}
  	$this->cookies = $correctCookies;
  }

	/**
	 * @param String $xmlData
	 * @throws HTError
	 */
	private function checkXmlData($xmlData)
	{
		$tmpXml = xml_parser_create();
		if(!xml_parse($tmpXml, $xmlData, true))
		{
			throw new HTError($xmlData, false);
		}
		xml_parser_free($tmpXml);

		$tmpXml = new DOMDocument('1.0', 'UTF-8');
		$tmpXml->loadXML($xmlData);
		$filename = $tmpXml->getElementsByTagName('FileName');
		if($filename->length == 0)
		{
			throw new HTError($xmlData, true);
		}
		if($filename->item(0)->nodeValue == self::ERROR_FILE)
		{
			throw new HTError($xmlData, true);
		}
	}

	/**
	 * @param String $file
	 * @param Array $params
	 * @return String
	 */
	private function buildUrl($file, $params)
	{
		$url = $this->host.$file."?";
		foreach ($params as $param => $value)
		{
			$url .= $param."=".urlencode($value)."&";
		}
		return substr($url,0,-1);
	}

	/**
	 * Return HTFlags object
	 *
	 * @param Integer $teamId
	 * @param String $fromDate
	 * @param Boolean $useEnglishName
	 * @return HTFlags
	 */
	public function getFlagsHome($teamId = null, $fromDate = null, $useEnglishName = true)
	{
		$team = $this->getTeam($teamId);
		if(!isset($this->flagsHome[$team->getTeamId()]) || $this->flagsHome[$team->getTeamId()] === null)
		{
			$startDate = $team->getActivationDate();
			if($fromDate !== null && $fromDate >= $startDate)
			{
				$startDate = $fromDate;
			}
			$leagueId = $this->getLeague($team->getLeagueLevelId())->getLeagueId();
			$season = $this->getWorldDetails()->getLeagueById($leagueId)->getSeasonNumber();
			$tmp = array();
			for($s=$season; $s>0; $s--)
			{
				$archive = $this->getSeniorTeamArchiveMatches($team->getTeamId(), null, null, $s);
				for($m=$archive->getMatchNumber(); $m>0; $m--)
				{
					$match = $archive->getMatch($m);
					if(($match->getType() == 8 || $match->getType() == 9) && $match->getDate()>=$startDate && $match->getHomeTeamId()==$team->getTeamId())
					{
						$otherLeagueId = $this->getTeam($match->getAwayTeamId())->getLeagueId();
						if($otherLeagueId == 0)
						{
							$otherLeagueId = $this->getRegion($this->getTeam($match->getAwayTeamId())->getRegionId())->getLeagueId();
						}
						$tmp[$otherLeagueId] = true;
					}
					if($match->getDate()<$startDate)
					{
						break 2;
					}
				}
			}
			$flags = array();
			foreach(array_keys($tmp) as $id)
			{
				$league = $this->getWorldDetails()->getLeagueById($id);
				if($useEnglishName)
				{
					$name = $league->getEnglishName();
				}
				else
				{
					$name = $league->getCountryName();
				}
				$flags[$name] = $league;
			}
			ksort($flags);
			$this->flagsHome[$team->getTeamId()] = new HTFlags($flags);
		}
		return $this->flagsHome[$team->getTeamId()];
	}

	/**
	 * Clear cache of home maches flags for a team
	 *
	 * @param Integer $teamId
	 */
	public function clearFlagsHome($teamId = null)
	{
		if($teamId === null)
		{
			$teamId = $this->getTeam()->getTeamId();
		}
		$this->flagsHome[$teamId] = null;
	}

	/**
	 * Clear cache of all away matches flags
	 */
	public function clearAllFlagsHome()
	{
		$this->flagsHome = array();
	}

	/**
	 * Return HTFlags object
	 *
	 * @param Integer $teamId
	 * @param String $fromDate
	 * @param Boolean $useEnglishName
	 * @return HTFlags
	 */
	public function getFlagsAway($teamId = null, $fromDate = null, $useEnglishName = true)
	{
		$team = $this->getTeam($teamId);
		if(!isset($this->flagsAway[$team->getTeamId()]) || $this->flagsAway[$team->getTeamId()] === null)
		{
			$startDate = $team->getActivationDate();
			if($fromDate !== null && $fromDate >= $startDate)
			{
				$startDate = $fromDate;
			}
			$leagueId = $this->getLeague($team->getLeagueLevelId())->getLeagueId();
			$season = $this->getWorldDetails()->getLeagueById($leagueId)->getSeasonNumber();
			$tmp = array();
			for($s=$season; $s>0; $s--)
			{
				$archive = $this->getSeniorTeamArchiveMatches($team->getTeamId(), null, null, $s);
				for($m=$archive->getMatchNumber(); $m>0; $m--)
				{
					$match = $archive->getMatch($m);
					if(($match->getType() == 8 || $match->getType() == 9) && $match->getDate()>=$startDate && $match->getAwayTeamId()==$team->getTeamId())
					{
						$otherLeagueId = $this->getTeam($match->getHomeTeamId())->getLeagueId();
						if($otherLeagueId == 0)
						{
							$otherLeagueId = $this->getRegion($this->getTeam($match->getHomeTeamId())->getRegionId())->getLeagueId();
						}
						$tmp[$otherLeagueId] = true;
					}
					if($match->getDate()<$startDate)
					{
						break 2;
					}
				}
			}
			$flags = array();
			foreach(array_keys($tmp) as $id)
			{
				$league = $this->getWorldDetails()->getLeagueById($id);
				if($useEnglishName)
				{
					$name = $league->getEnglishName();
				}
				else
				{
					$name = $league->getCountryName();
				}
				$flags[$name] = $league;
			}
			ksort($flags);
			$this->flagsAway[$team->getTeamId()] = new HTFlags($flags);
		}
		return $this->flagsAway[$team->getTeamId()];
	}

	/**
	 * Clear cache of away maches flags for a team
	 *
	 * @param Integer $teamId
	 */
	public function clearFlagsAway($teamId = null)
	{
		if($teamId === null)
		{
			$teamId = $this->getTeam()->getTeamId();
		}
		$this->flagsAway[$teamId] = null;
	}

	/**
	 * Clear cache of all home matches flags
	 */
	public function clearAllFlagsAway()
	{
		$this->flagsAway = array();
	}

	/**
	 * Return HTFlags object
	 *
	 * @param Integer $teamId
	 * @param Boolean $useEnglishName
	 * @return HTFlags
	 */
	public function getFlagsPlayers($teamId = null, $useEnglishName = true)
	{
		$team = $this->getTeam($teamId);
		if(!isset($this->flagsPlayers[$team->getTeamId()]) || $this->flagsPlayers[$team->getTeamId()] === null)
		{
			$players = $this->getTeamPlayers($team->getTeamId());
			$worldDetails = $this->getWorldDetails();
			$tmp = array();
			for($i=1; $i<=$players->getNumberPlayers(); $i++)
			{
				$tmp[] = $worldDetails->getLeagueByCountryId($players->getPlayer($i)->getCountryId())->getId();
			}
			$tmp = array_unique($tmp);
			sort($tmp);
			$flags = array();
			foreach($tmp as $id)
			{
				$league = $worldDetails->getLeagueById($id);
				if($useEnglishName)
				{
					$name = $league->getEnglishName();
				}
				else
				{
					$name = $league->getCountryName();
				}
				$flags[$name] = $league;
			}
			$this->flagsPlayers[$team->getTeamId()] = new HTFlags($flags);
		}
		return $this->flagsPlayers[$team->getTeamId()];
	}

	/**
	 * Clear cache of team players flags
	 *
	 * @param Integer $teamId
	 */
	public function clearFlagsPlayers($teamId = null)
	{
		if($teamId === null)
		{
			$teamId = $this->getTeam()->getTeamId();
		}
		$this->flagsPlayers[$teamId] = null;
	}

	/**
	 * Clear cache of all team players flags
	 */
	public function clearAllFlagsPlayers()
	{
		$this->flagsPlayers = array();
	}

	/**
	 * Return HTFlags object
	 *
	 * @param Integer $teamId
	 * @param Boolean $useEnglishName
	 * @return HTFlags
	 */
	public function getFlagsRaisedPlayers($teamId = null, $useEnglishName = true)
	{
		$team = $this->getTeam($teamId);
		if(!isset($this->flagsRaisedPlayers[$team->getTeamId()]) || $this->flagsRaisedPlayers[$team->getTeamId()] === null)
		{
			$players = $this->getTeamOldPlayers($team->getTeamId());
			$worldDetails = $this->getWorldDetails();
			$tmp = array();
			for($i=1; $i<=$players->getNumberPlayers(); $i++)
			{
				$tmp[] = $worldDetails->getLeagueByName($players->getPlayer($i)->getLeagueName())->getId();
			}
			$tmp = array_unique($tmp);
			sort($tmp);
			$flags = array();
			foreach($tmp as $id)
			{
				$league = $worldDetails->getLeagueById($id);
				if($useEnglishName)
				{
					$name = $league->getEnglishName();
				}
				else
				{
					$name = $league->getCountryName();
				}
				$flags[$name] = $league;
			}
			$this->flagsRaisedPlayers[$team->getTeamId()] = new HTFlags($flags);
		}
		return $this->flagsRaisedPlayers[$team->getTeamId()];
	}

	/**
	 * Clear cache of team raised players flags
	 *
	 * @param Integer $teamId
	 */
	public function clearFlagsRaisedPlayers($teamId = null)
	{
		if($teamId === null)
		{
			$teamId = $this->getTeam()->getTeamId();
		}
		$this->flagsRaisedPlayers[$teamId] = null;
	}

	/**
	 * Clear cache of all team raised players flags
	 */
	public function clearAllFlagsRaisedPlayers()
	{
		$this->flagsRaisedPlayers = array();
	}

	/**
	 * Is match a derby ?
	 *
	 * @param Integer $matchId
	 * @param Boolean $isYouth
	 * @return Boolean
	 */
	public function isMatchADerby($matchId, $isYouth = false)
	{
		if($isYouth)
		{
			$match = $this->getYouthMatchDetails($matchId);
		}
		else
		{
			$match = $this->getSeniorMatchDetails($matchId);
		}
		return $this->getTeam($match->getHomeTeam()->getId())->getRegionId() == $this->getTeam($match->getAwayTeam()->getId())->getRegionId();
	}
}
class HTXml
{
	/**
	 * @var DOMDocument
	 */
	protected $xml;
	protected $xmlText;

	/**
	 * Return XML data
	 *
	 * @param Boolean $asObject
	 * @return DOMDocument
	 */
	public function getXml($asObject = true)
	{
		if($this->xml === null || @$this->xml->saveXML() === null)
		{
			$this->xml = new DOMDocument('1.0', 'UTF-8');
			$this->xml->loadXML($this->xmlText);
		}
		if($asObject == true)
		{
			return $this->xml;
		}
		return $this->xml->saveXML();
	}
}
class HTFunction
{
	const PLAYERURL = '/Common/Players/PlayerDetails.aspx?';

	/**
	 * Convert date to a specific format
	 *
	 * @param String $date
	 * @param String $format
	 * @return String
	 */
	public static function convertDate($date, $format)
	{
		return date($format, strtotime($date));
	}

	/**
	 * Change url of player link in match text
	 *
	 * @param String $text
	 * @param String $newUrl
	 * @return String
	 */
	public static function updatePlayerUrl($text, $newUrl)
	{
		return str_replace(self::PLAYERURL , $newUrl, $text);
	}
}
class HTGlobal extends HTXml
{
	protected $userId = null;
	protected $fetchedDate = null;
	protected $xmlFileVersion = null;
	protected $xmlFileName = null;

	/**
	 * Create an instance
	 *
	 * @param String $xml
	 */
	public function __construct($xml)
	{
		$this->xmlText = $xml;
		$this->xml = new DOMDocument('1.0', 'UTF-8');
		$this->xml->loadXml($xml);
	}

	/**
	 * Return UserId of connected user
	 *
	 * @return Integer
	 */
	public function getUserId()
	{
		if(!isset($this->userId) || $this->userId === null)
		{
			$this->userId = $this->getXml()->getElementsByTagName('UserID')->item(0)->nodeValue;
		}
		return $this->userId;
	}

	/**
	 * Return fetched date of xml file
	 *
	 * @return String
	 */
	public function getFetchedDate($format = null)
	{
		if(!isset($this->fetchedDate) || $this->fetchedDate === null)
		{
			$this->fetchedDate = $this->getXml()->getElementsByTagName('FetchedDate')->item(0)->nodeValue;
			if($format !== null)
			{
				$this->fetchedDate = HTFunction::convertDate($this->fetchedDate, $format);
			}
		}
		return $this->fetchedDate;
	}

	/**
	 * Return version of xml file
	 *
	 * @return Integer
	 */
	public function getXmlFileVersion()
	{
		if(!isset($this->xmlFileVersion) || $this->xmlFileVersion === null)
		{
			$this->xmlFileVersion = $this->getXml()->getElementsByTagName('Version')->item(0)->nodeValue;
		}
		return $this->xmlFileVersion;
	}

	/**
	 * Return name of xml file
	 *
	 * @return String
	 */
	public function getXmlFileName()
	{
		if(!isset($this->xmlFileName) || $this->xmlFileName === null)
		{
			$this->xmlFileName = $this->getXml()->getElementsByTagName('FileName')->item(0)->nodeValue;
		}
		return $this->xmlFileName;
	}
}
class HTCommonSubscriber extends HTCommonTeam
{
	protected $isHtSupporter = null;

	/**
	 * Is the user Hattrick Supporter ?
	 *
	 * @return Boolean
	 */
	public function isHtSupporter()
	{
		if(!isset($this->isHtSupporter) || $this->isHtSupporter === null)
		{
			$this->isHtSupporter = strtolower($this->getXml()->getElementsByTagName('UserIsSupporter')->item(0)->nodeValue) == "true";
		}
		return $this->isHtSupporter;
	}
}
class HTCommonTeam extends HTGlobal
{
	protected $teamId = null;
	protected $teamName = null;

	/**
 	 * Return Team Id of connected user
	 *
	 * @return Integer
	 */
	public function getTeamId()
	{
		if(!isset($this->teamId) || $this->teamId === null)
		{
			$this->teamId = $this->getXml()->getElementsByTagName('TeamID')->item(0)->nodeValue;
		}
		return $this->teamId;
	}

	/**
	 * Return Team name of connected user
	 *
	 * @return String
	 */
	public function getTeamName()
	{
		if(!isset($this->teamName) || $this->teamName === null)
		{
			$this->teamName = $this->getXml()->getElementsByTagName('TeamName')->item(0)->nodeValue;
		}
		return $this->teamName;
	}
}
class HTCommonLeague extends HTGlobal
{
	protected $leagueId = null;
	protected $leagueName = null;

	/**
	 * Return league id
	 *
	 * @return Integer
	 */
	public function getLeagueId()
	{
		if(!isset($this->leagueId) || $this->leagueId === null)
		{
			$this->leagueId = $this->getXml()->getElementsByTagName('LeagueID')->item(0)->nodeValue;
		}
		return $this->leagueId;
	}

	/**
	 * Return league name
	 *
	 * @return String
	 */
	public function getLeagueName()
	{
		if(!isset($this->leagueName) || $this->leagueName === null)
		{
			$this->leagueName = $this->getXml()->getElementsByTagName('LeagueName')->item(0)->nodeValue;
		}
		return $this->leagueName;
	}
}
class HTCommonLeagueLevel extends HTGlobal
{
	protected $leagueLevelId = null;
	protected $leagueLevelName = null;

	/**
	 * Return league level id
	 *
	 * @return Integer
	 */
	public function getLeagueLevelId()
	{
		if(!isset($this->leagueLevelId) || $this->leagueLevelId === null)
		{
			$this->leagueLevelId = $this->getXml()->getElementsByTagName('LeagueLevelUnitID')->item(0)->nodeValue;
		}
		return $this->leagueLevelId;
	}

	/**
	 * Return league level name
	 *
	 * @return Integer
	 */
	public function getLeagueLevelName()
	{
		if(!isset($this->leagueLevelName) || $this->leagueLevelName === null)
		{
			$this->leagueLevelName = $this->getXml()->getElementsByTagName('LeagueLevelUnitName')->item(0)->nodeValue;
		}
		return $this->leagueLevelName;
	}
}
class HTClub extends HTCommonTeam
{
	private $specialists = null;
	private $youthSquad = null;

	/**
	 * Return specialists object
	 *
	 * @param Integer $countryCurrency (Constant taken from HTMoney class)
	 * @return HTSpecialists
	 */
	public function getSpecialists($countryCurrency = null)
	{
		if(!isset($this->specialists) || $this->specialists === null)
		{
			$this->specialists = new HTSpecialists($this->getXml(), $countryCurrency);
		}
		return $this->specialists;
	}

	/**
	 * Return HTYouthSquad object
	 *
	 * @return HTYouthSquad
	 */
	public function getYouthSquad()
	{
		if(!isset($this->youthSquad) || $this->youthSquad === null)
		{
			$this->youthSquad = new HTYouthSquad($this->getXml());
		}
		return $this->youthSquad;
	}
}
class HTYouthSquad extends HTXml
{
	private $investment;
	private $hasPromoted;
	private $youthLevel;

	/**
	 * @param DOMDocument $xml
	 */
	public function __construct($xml)
	{
		$this->xmlText = $xml->saveXML();
		$this->xml = $xml;
	}

	/**
	 * Return invesment sum
	 *
	 * @return Integer
	 */
	public function getInvestment()
	{
		if(!isset($this->investment) || $this->investment === null)
		{
			$this->investment = $this->getXml()->getElementsByTagName('Investment')->item(0)->nodeValue;
		}
		return $this->investment;
	}

	/**
	 * Youth promoted this week ?
	 *
	 * @return Boolean
	 */
	public function hasPromoted()
	{
		if(!isset($this->hasPromoted) || $this->hasPromoted === null)
		{
			$this->hasPromoted = strtolower($this->getXml()->getElementsByTagName('HasPromoted')->item(0)->nodeValue) == "true";
		}
		return $this->hasPromoted;
	}

	/**
	 * Return youth squad level
	 *
	 * @return Integer
	 */
	public function getYouthLevel()
	{
		if(!isset($this->youthLevel) || $this->youthLevel === null)
		{
			$this->youthLevel = $this->getXml()->getElementsByTagName('YouthLevel')->item(0)->nodeValue;
		}
		return $this->youthLevel;
	}
}
class HTSpecialists extends HTXml
{
	private $money = null;
	private $assistantTrainers = null;
	private $assistantTrainersCost = null;
	private $keeperTrainers = null;
	private $keeperTrainersCost = null;
	private $psychologists = null;
	private $psychologistsCost = null;
	private $pressSpokesmen = null;
	private $pressSpokesmenCost = null;
	private $economists = null;
	private $economistsCost = null;
	private $physiotherapists = null;
	private $physiotherapistsCost = null;
	private $doctors = null;
	private $doctorsCost = null;
	private $totalCosts = null;
	const BASE_COST = 18000;

	/**
	 * @param DOMDocument $xml
	 */
	public function __construct($xml, $money)
	{
		$this->xmlText = $xml->saveXML();
		$this->xml = $xml;
		$this->money = $money;
	}

	/**
	 * Return number of assistant trainers
	 *
	 * @return Integer
	 */
	public function getAssistantTrainers()
	{
		if(!isset($this->assistantTrainers) || $this->assistantTrainers === null)
		{
			$this->assistantTrainers = $this->getXml()->getElementsByTagName('AssistantTrainers')->item(0)->nodeValue;
		}
		return $this->assistantTrainers;
	}

	/**
	 * Return assistant trainers cost
	 *
	 * @return Integer
	 */
	public function getAssistantTrainersCost()
	{
		if(!isset($this->assistantTrainersCost) || $this->assistantTrainersCost === null)
		{
			$this->assistantTrainersCost = $this->getAssistantTrainers() * self::BASE_COST ;
			if($this->money !== null)
			{
				$this->assistantTrainersCost = HTMoney::convert($this->assistantTrainersCost, $this->money);
			}
		}
		return $this->assistantTrainersCost;
	}

	/**
	 * Return number of keeper trainers
	 *
	 * @return Integer
	 */
	public function getKeeperTrainers()
	{
		if(!isset($this->keeperTrainers) || $this->keeperTrainers === null)
		{
			$this->keeperTrainers = $this->getXml()->getElementsByTagName('KeeperTrainers')->item(0)->nodeValue;
		}
		return $this->keeperTrainers;
	}

	/**
	 * Return keeper trainers cost
	 *
	 * @return Integer
	 */
	public function getKeeperTrainersCost()
	{
		if(!isset($this->keeperTrainersCost) || $this->keeperTrainersCost === null)
		{
			$this->keeperTrainersCost = $this->getKeeperTrainers() * self::BASE_COST ;
			if($this->money !== null)
			{
				$this->keeperTrainersCost = HTMoney::convert($this->keeperTrainersCost, $this->money);
			}
		}
		return $this->keeperTrainersCost;
	}

	/**
	 * Return number of psychologists
	 *
	 * @return Integer
	 */
	public function getPsychologists()
	{
		if(!isset($this->psychologists) || $this->psychologists === null)
		{
			$this->psychologists = $this->getXml()->getElementsByTagName('Psychologists')->item(0)->nodeValue;
		}
		return $this->psychologists;
	}

	/**
	 * Return psychologists cost
	 *
	 * @return Integer
	 */
	public function getPsychologistsCost()
	{
		if(!isset($this->psychologistsCost) || $this->psychologistsCost === null)
		{
			$this->psychologistsCost = $this->getPsychologists() * self::BASE_COST ;
			if($this->money !== null)
			{
				$this->psychologistsCost = HTMoney::convert($this->psychologistsCost, $this->money);
			}
		}
		return $this->psychologistsCost;
	}

	/**
	 * Return number of press spokemen
	 *
	 * @return Integer
	 */
	public function getPressSpoken()
	{
		if(!isset($this->pressSpokesmen) || $this->pressSpokesmen === null)
		{
			$this->pressSpokesmen = $this->getXml()->getElementsByTagName('PressSpokesmen')->item(0)->nodeValue;
		}
		return $this->pressSpokesmen;
	}

	/**
	 * Return press spokemen cost
	 *
	 * @return Integer
	 */
	public function getPressSpokenCost()
	{
		if(!isset($this->pressSpokesmenCost) || $this->pressSpokesmenCost === null)
		{
			$this->pressSpokesmenCost = $this->getPressSpoken() * self::BASE_COST ;
			if($this->money !== null)
			{
				$this->pressSpokesmenCost = HTMoney::convert($this->pressSpokesmenCost, $this->money);
			}
		}
		return $this->pressSpokesmenCost;
	}

	/**
	 * Return number of economists
	 *
	 * @return Integer
	 */
	public function getEconomists()
	{
		if(!isset($this->economists) || $this->economists === null)
		{
			$this->economists = $this->getXml()->getElementsByTagName('Economists')->item(0)->nodeValue;
		}
		return $this->economists;
	}

	/**
	 * Return economists cost
	 *
	 * @return Integer
	 */
	public function getEconomistsCost()
	{
		if(!isset($this->economistsCost) || $this->economistsCost === null)
		{
			$this->economistsCost = $this->getEconomists() * self::BASE_COST ;
			if($this->money !== null)
			{
				$this->economistsCost = HTMoney::convert($this->economistsCost, $this->money);
			}
		}
		return $this->economistsCost;
	}

	/**
	 * Return number of physiotherapists
	 *
	 * @return Integer
	 */
	public function getPhysiotherapists()
	{
		if(!isset($this->physiotherapists) || $this->physiotherapists === null)
		{
			$this->physiotherapists = $this->getXml()->getElementsByTagName('Physiotherapists')->item(0)->nodeValue;
		}
		return $this->physiotherapists;
	}

	/**
	 * Return physiotherapists cost
	 *
	 * @return Integer
	 */
	public function getPhysiotherapistsCost()
	{
		if(!isset($this->physiotherapistsCost) || $this->physiotherapistsCost === null)
		{
			$this->physiotherapistsCost = $this->getPhysiotherapists() * self::BASE_COST ;
			if($this->money !== null)
			{
				$this->physiotherapistsCost = HTMoney::convert($this->physiotherapistsCost, $this->money);
			}
		}
		return $this->physiotherapistsCost;
	}

	/**
	 * Return number of doctors
	 *
	 * @return Integer
	 */
	public function getDoctors()
	{
		if(!isset($this->doctors) || $this->doctors === null)
		{
			$this->doctors = $this->getXml()->getElementsByTagName('Doctors')->item(0)->nodeValue;
		}
		return $this->doctors;
	}

	/**
	 * Return doctors cost
	 *
	 * @return Integer
	 */
	public function getDoctorsCost()
	{
		if(!isset($this->doctorsCost) || $this->doctorsCost === null)
		{
			$this->doctorsCost = $this->getDoctors() * self::BASE_COST ;
			if($this->money !== null)
			{
				$this->doctorsCost = HTMoney::convert($this->doctorsCost, $this->money);
			}
		}
		return $this->doctorsCost;
	}

	/**
	 * Return total specialists costs
	 *
	 * @return Integer
	 */
	public function getTotalCosts()
	{
		if(!isset($this->totalCosts) || $this->totalCosts === null)
		{
			$this->totalCosts = $this->getKeeperTrainersCost()
												+	$this->getAssistantTrainersCost()
												+	$this->getPsychologistsCost()
												+	$this->getPressSpokenCost()
												+	$this->getEconomistsCost()
												+	$this->getPhysiotherapistsCost()
												+	$this->getDoctorsCost();
		}
		return $this->totalCosts;
	}
}
class HTTeam extends HTCommonTeam
{
	private $languageId = null;
	private $languageName = null;
	private $isHTSup = null;
	private $loginName = null;
	private $name = null;
	private $icq = null;
	private $signupDate = null;
	private $activationDate = null;
	private $lastLoginDate = null;
	private $shortTeamName = null;
	private $arenaId = null;
	private $arenaName = null;
	private $leagueId = null;
	private $leagueName = null;
	private $regionId = null;
	private $regionName = null;
	private $trainerId = null;
	private $homePageUrl = null;
	private $dress1 = null;
	private $dress2 = null;
	private $isBot = null;
	private $botDate = null;
	private $isInCup = null;
	private $cupId = null;
	private $cupName = null;
	private $friendlyTeamId = null;
	private $leagueLevelAvailable = null;
	private $leagueLevelId = null;
	private $leagueLevelName = null;
	private $leagueLevel = null;
	private $numberVictories = null;
	private $numberVictoriesAvailable = null;
	private $numberUndefeatAvailable = null;
	private $numberUndefeat = null;
	private $teamRankAvailable = null;
	private $teamRank = null;
	private $logoUrl = null;
	private $fanClubId = null;
	private $fanClubName = null;
	private $numberGuestbookMessages = null;
	private $pressSubject = null;
	private $pressText = null;
	private $pressDate = null;
	private $youthTeamId = null;
	private $numberOfVisits = null;

	public function __construct($xml)
	{
		parent::__construct($xml);
	}

	/**
	 * Return UserId of connected user
	 *
	 * @return Integer
	 */
	public function getUserId()
	{
		if(!isset($this->userId) || $this->userId === null)
		{
			$this->userId = $this->getXml()->getElementsByTagName('UserID')->item(1)->nodeValue;
		}
		return $this->userId;
	}

	/**
	 * Return team user's language id
	 *
	 * @return Integer
	 */
	public function getLanguageId()
	{
		if(!isset($this->languageId) || $this->languageId === null)
		{
			$this->languageId = $this->getXml()->getElementsByTagName('LanguageID')->item(0)->nodeValue;
		}
		return $this->languageId;
	}

	/**
	 * Return team user's language name
	 *
	 * @return String
	 */
	public function getLanguageName()
	{
		if(!isset($this->languageName) || $this->languageName === null)
		{
			$this->languageName = $this->getXml()->getElementsByTagName('LanguageName')->item(0)->nodeValue;
		}
		return $this->languageName;
	}

	/**
	 * Does the this is HT-Supporter ?
	 *
	 * @return Boolean
	 */
	public function isHtSupporter()
	{
		if(!isset($this->isHTSup) || $this->isHTSup === null)
		{
			$this->isHTSup = strtolower($this->getXml()->getElementsByTagName('HasSupporter')->item(0)->nodeValue) == "true";
		}
		return $this->isHTSup;
	}

	/**
	 * Return user public loginname
	 *
	 * @return String
	 */
	public function getLoginName()
	{
		if(!isset($this->loginName) || $this->loginName === null)
		{
			$this->loginName = $this->getXml()->getElementsByTagName('Loginname')->item(0)->nodeValue;
		}
		return $this->loginName;
	}

	/**
	 * Return irl user name if he made it public
	 *
	 * @return String
	 */
	public function getName()
	{
		if(!isset($this->name) || $this->name === null)
		{
			$this->name = $this->getXml()->getElementsByTagName('Name')->item(0)->nodeValue;
		}
		if($this->name == 'HIDDEN')
		{
			return null;
		}
		return $this->loginName;
	}

	/**
	 * Return user icq number
	 *
	 * @return String
	 */
	public function getIcq()
	{
		if(!isset($this->icq) || $this->icq === null)
		{
			$this->icq = $this->getXml()->getElementsByTagName('ICQ')->item(0)->nodeValue;
		}
		return $this->loginName;
	}

	/**
	 * Return signup date
	 *
	 * @param String $format (php date() function format)
	 * @return String
	 */
	public function getSignupDate($format = null)
	{
		if(!isset($this->signupDate) || $this->signupDate === null)
		{
			$this->signupDate = $this->getXml()->getElementsByTagName('SignupDate')->item(0)->nodeValue;
			if($format !== null)
			{
				$this->signupDate = HTFunction::convertDate($this->signupDate, $format);
			}
		}
		return $this->signupDate;
	}

	/**
	 * Return activation date
	 *
	 * @param String $format (php date() function format)
	 * @return String
	 */
	public function getActivationDate($format = null)
	{
		if(!isset($this->activationDate) || $this->activationDate === null)
		{
			$this->activationDate = $this->getXml()->getElementsByTagName('ActivationDate')->item(0)->nodeValue;
			if($format !== null)
			{
				$this->activationDate = HTFunction::convertDate($this->activationDate, $format);
			}
		}
		return $this->activationDate;
	}

	/**
	 * Return last login date
	 *
	 * @param String $format (php date() function format)
	 * @return String
	 */
	public function getLastLoginDate($format = null)
	{
		if(!isset($this->lastLoginDate) || $this->lastLoginDate === null)
		{
			$this->lastLoginDate = $this->getXml()->getElementsByTagName('LastLoginDate')->item(0)->nodeValue;
			if($format !== null)
			{
				$this->lastLoginDate = HTFunction::convertDate($this->lastLoginDate, $format);
			}
		}
		return $this->lastLoginDate;
	}

	/**
	 * Return short team nam
	 *
	 * @return String
	 */
	public function getShortTeamName()
	{
		if(!isset($this->shortTeamName) || $this->shortTeamName === null)
		{
			$this->shortTeamName = $this->getXml()->getElementsByTagName('ShortTeamName')->item(0)->nodeValue;
		}
		return $this->shortTeamName;
	}

	/**
	 * Return arena id
	 *
	 * @return Integer
	 */
	public function getArenaId()
	{
		if(!isset($this->arenaId) || $this->arenaId === null)
		{
			$this->arenaId = $this->getXml()->getElementsByTagName('ArenaID')->item(0)->nodeValue;
		}
		return $this->arenaId;
	}

	/**
	 * Return arena name
	 *
	 * @return String
	 */
	public function getArenaName()
	{
		if(!isset($this->arenaName) || $this->arenaName === null)
		{
			$this->arenaName = $this->getXml()->getElementsByTagName('ArenaName')->item(0)->nodeValue;
		}
		return $this->arenaName;
	}

	/**
	 * Return league id
	 *
	 * @return Integer
	 */
	public function getLeagueId()
	{
		if(!isset($this->leagueId) || $this->leagueId === null)
		{
			$this->leagueId = $this->getXml()->getElementsByTagName('LeagueID')->item(0)->nodeValue;
		}
		return $this->leagueId;
	}

	/**
	 * Return league name
	 *
	 * @return String
	 */
	public function getLeagueName()
	{
		if(!isset($this->leagueName) || $this->leagueName === null)
		{
			$this->leagueName = $this->getXml()->getElementsByTagName('LeagueName')->item(0)->nodeValue;
		}
		return $this->leagueName;
	}

	/**
	 * Return region id
	 *
	 * @return Integer
	 */
	public function getRegionId()
	{
		if(!isset($this->regionId) || $this->regionId === null)
		{
			$this->regionId = $this->getXml()->getElementsByTagName('RegionID')->item(0)->nodeValue;
		}
		return $this->regionId;
	}

	/**
	 * Return region name
	 *
	 * @return String
	 */
	public function getRegionName()
	{
		if(!isset($this->regionName) || $this->regionName === null)
		{
			$this->regionName = $this->getXml()->getElementsByTagName('RegionName')->item(0)->nodeValue;
		}
		return $this->regionName;
	}

	/**
	 * Return trainer id
	 *
	 * @return Integer
	 */
	public function getTrainerId()
	{
		if(!isset($this->trainerId) || $this->trainerId === null)
		{
			$this->trainerId = $this->getXml()->getElementsByTagName('PlayerID')->item(0)->nodeValue;
		}
		return $this->trainerId;
	}

	/**
	 * Return home page url
	 *
	 * @return String
	 */
	public function getHomePageUrl()
	{
		if(!isset($this->homePageUrl) || $this->homePageUrl === null)
		{
			$this->homePageUrl = $this->getXml()->getElementsByTagName('HomePage')->item(0)->nodeValue;
			if(substr($this->homePageUrl, 0, 7) !== 'http://')
			{
				$this->homePageUrl = 'http://'.$this->homePageUrl;
			}
		}
		return $this->homePageUrl;
	}

	/**
	 * Return code of main dress
	 *
	 * @return String
	 */
	public function getDessCode()
	{
		if(!isset($this->dress1) || $this->dress1 === null)
		{
			$this->dress1 = $this->getXml()->getElementsByTagName('Dress')->item(0)->nodeValue;
		}
		return $this->dress1;
	}

	/**
	 * Return code of alternate dress
	 *
	 * @return String
	 */
	public function getDessAlternateCode()
	{
		if(!isset($this->dress2) || $this->dress2 === null)
		{
			$this->dress2 = $this->getXml()->getElementsByTagName('DressAlternate')->item(0)->nodeValue;
		}
		return $this->dress2;
	}

	/**
	 * Is the team a bot ?
	 *
	 * @return Boolean
	 */
	public function isBot()
	{
		if(!isset($this->isBot) || $this->isBot === null)
		{
			$this->isBot = strtolower($this->getXml()->getElementsByTagName('IsBot')->item(0)->nodeValue) == "true";
		}
		return $this->isBot;
	}

	/**
	 * Return date when team became bot, if it's a bot
	 *
	 * @param String $format (php date() function format)
	 * @return String
	 */
	public function getBotDate($format = null)
	{
		if($this->isBot())
		{
			if(!isset($this->botDate) || $this->botDate === null)
			{
				$this->botDate = $this->getXml()->getElementsByTagName('BotSince')->item(0)->nodeValue;
				if($format !== null)
				{
					$this->botDate = HTFunction::convertDate($this->botDate, $format);
				}
			}
			return $this->botDate;
		}
		return null;
	}

	/**
	 * Is the team still in cup ?
	 *
	 * @return Boolean
	 */
	public function isInCup()
	{
		if(!isset($this->isInCup) || $this->isInCup === null)
		{
			$this->isInCup = strtolower($this->getXml()->getElementsByTagName('StillInCup')->item(0)->nodeValue) == "true";
		}
		return $this->isInCup;
	}

	/**
	 * Return cup id
	 *
	 * @return Integer
	 */
	public function getCupId()
	{
		if($this->isInCup())
		{
			if(!isset($this->cupId) || $this->cupId === null)
			{
				$this->cupId = $this->getXml()->getElementsByTagName('CupID')->item(0)->nodeValue;
			}
			return $this->cupId;
		}
		return null;
	}

	/**
	 * Return cup name
	 *
	 * @return String
	 */
	public function getCupName()
	{
		if($this->isInCup())
		{
			if(!isset($this->cupName) || $this->cupName === null)
			{
				$this->cupName = $this->getXml()->getElementsByTagName('CupName')->item(0)->nodeValue;
			}
			return $this->cupName;
		}
		return null;
	}

	/**
	 * Is League data available ?
	 *
	 * @return Boolean
	 */
	public  function isLeagueLevelAvailable()
	{
		if(!isset($this->leagueLevelAvailable) || $this->leagueLevelAvailable === null)
		{
			$this->leagueLevelAvailable = $this->getXml()->getElementsByTagName('LeagueLevelUnit')->item(0)->hasChildNodes();
		}
		return $this->leagueLevelAvailable;
	}

	/**
	 * Return league level
	 *
	 * @return Integer
	 */
	public function getLeagueLevel()
	{
		if($this->isLeagueLevelAvailable())
		{
			if(!isset($this->leagueLevel) || $this->leagueLevel === null)
			{
				$this->leagueLevel = $this->getXml()->getElementsByTagName('LeagueLevel')->item(0)->nodeValue;
			}
			return $this->leagueLevel;
		}
		return null;
	}

	/**
	 * Return league level id
	 *
	 * @return Integer
	 */
	public function getLeagueLevelId()
	{
		if($this->isLeagueLevelAvailable())
		{
			if(!isset($this->leagueLevelId) || $this->leagueLevelId === null)
			{
				$this->leagueLevelId = $this->getXml()->getElementsByTagName('LeagueLevelUnitID')->item(0)->nodeValue;
			}
			return $this->leagueLevelId;
		}
		return null;
	}

	/**
	 * Return league level name
	 *
	 * @return Integer
	 */
	public function getLeagueLevelName()
	{
		if($this->isLeagueLevelAvailable())
		{
			if(!isset($this->leagueLevelName) || $this->leagueLevelName === null)
			{
				$this->leagueLevelName = $this->getXml()->getElementsByTagName('LeagueLevelUnitName')->item(0)->nodeValue;
			}
			return $this->leagueLevelName;
		}
		return null;
	}

	/**
	 * Return opposite team id for friendly match
	 *
	 * @return Integer
	 */
	public function getFriendlyOppositeTeamId()
	{
		if(!isset($this->friendlyTeamId) || $this->friendlyTeamId === null)
		{
			$this->friendlyTeamId = $this->getXml()->getElementsByTagName('FriendlyTeamID')->item(0)->nodeValue;
		}
		if($this->friendlyTeamId == 0)
		{
			return null;
		}
		return $this->friendlyTeamId;
	}

	/**
	 * Return number of consecutive victories
	 *
	 * @return Integer
	 */
	public function getNumberOfVitories()
	{
		if(!isset($this->numberVictoriesAvailable) || $this->numberVictoriesAvailable === null)
		{
			$this->numberVictoriesAvailable = $this->getXml()->getElementsByTagName('NumberOfVictories')->item(0)->getAttribute('Available');
		}
		if($this->numberVictoriesAvailable == true)
		{
			if(!isset($this->numberVictories) || $this->numberVictories === null)
			{
				$this->numberVictories = $this->getXml()->getElementsByTagName('NumberOfVictories')->item(0)->nodeValue;
			}
			return $this->numberVictories;
		}
		return null;
	}

	/**
	 * Return number of consecutive undefeated match
	 *
	 * @return Integer
	 */
	public function getNumberOfUndefeat()
	{
		if(!isset($this->numberUndefeatAvailable) || $this->numberUndefeatAvailable === null)
		{
			$this->numberUndefeatAvailable = $this->getXml()->getElementsByTagName('NumberOfUndefeated')->item(0)->getAttribute('Available');
		}
		if($this->numberUndefeatAvailable == true)
		{
			if(!isset($this->numberUndefeat) || $this->numberUndefeat === null)
			{
				$this->numberUndefeat = $this->getXml()->getElementsByTagName('NumberOfUndefeated')->item(0)->nodeValue;
			}
			return $this->numberUndefeat;
		}
		return null;
	}

	/**
	 * Return team rank
	 *
	 * @return Integer
	 */
	public function getTeamRank()
	{
		if(!isset($this->teamRankAvailable) || $this->teamRankAvailable === null)
		{
			$this->teamRankAvailable = $this->getXml()->getElementsByTagName('TeamRank')->item(0)->getAttribute('Available');
		}
		if($this->teamRankAvailable == true)
		{
			if(!isset($this->teamRank) || $this->teamRank === null)
			{
				$this->teamRank = $this->getXml()->getElementsByTagName('TeamRank')->item(0)->nodeValue;
			}
			return $this->teamRank;
		}
		return null;
	}

	/**
	 * Return logo url
	 *
	 * @return String
	 */
	public function getLogoUrl()
	{
		if(!isset($this->logoUrl) || $this->logoUrl === null)
		{
			$url = $this->getXml()->getElementsByTagName('LogoURL')->item(0)->nodeValue;
			$this->logoUrl = str_replace("\\", "/", $url);
		}
		return $this->logoUrl;
	}

	/**
	 * Return fan club id
	 *
	 * @return Integer
	 */
	public function getFanClubId()
	{
		if($this->isHtSupporter())
		{
			if(!isset($this->fanClubId) || $this->fanClubId === null)
			{
				$this->fanClubId = $this->getXml()->getElementsByTagName('FanclubID')->item(0)->nodeValue;
			}
			return $this->fanClubId;
		}
		return null;
	}

	/**
	 * Return fan club name
	 *
	 * @return String
	 */
	public function getFanClubName()
	{
		if($this->isHtSupporter())
		{
			if(!isset($this->fanClubName) || $this->fanClubName === null)
			{
				$this->fanClubName = $this->getXml()->getElementsByTagName('FanclubName')->item(0)->nodeValue;
			}
			return $this->fanClubName;
		}
		return null;
	}

	/**
	 * Return number of messages in user guestbook
	 *
	 * @return Integer
	 */
	public function getNumberMessageInGuestbook()
	{
		if($this->isHtSupporter())
		{
			if(!isset($this->numberGuestbookMessages) || $this->numberGuestbookMessages === null)
			{
				$this->numberGuestbookMessages = $this->getXml()->getElementsByTagName('NumberOfGuestbookItems')->item(0)->nodeValue;
			}
			return $this->numberGuestbookMessages;
		}
		return null;
	}

	/**
	 * Return title of last press announcement
	 *
	 * @return String
	 */
	public function getPressAnnouncementTitle()
	{
		if($this->isHtSupporter())
		{
			if(!isset($this->pressSubject) || $this->pressSubject === null)
			{
				$node = $this->getXml()->getElementsByTagName('Subject');
				if($node !== null && $node->length)
				{
					$this->pressSubject = $node->item(0)->nodeValue;
				}
				else
				{
					$this->pressSubject = false;
				}
			}
			return $this->pressSubject;
		}
		return null;
	}

	/**
	 * Return content of last press announcement
	 *
	 * @return String
	 */
	public function getPressAnnouncementText()
	{
		if($this->isHtSupporter())
		{
			if(!isset($this->pressText) || $this->pressText === null)
			{
				$node = $this->getXml()->getElementsByTagName('Body');
				if($node !== null && $node->length)
				{
					$this->pressText = $node->item(0)->nodeValue;
				}
				else
				{
					$this->pressText = false;
				}
			}
			return $this->pressText;
		}
		return null;
	}

	/**
	 * Return content of last press announcement
	 *
	 * @param String $format (php date() function format)
	 * @return String
	 */
	public function getPressAnnouncementDate($format = null)
	{
		if($this->isHtSupporter())
		{
			if(!isset($this->pressDate) || $this->pressDate === null)
			{
				$node = $this->getXml()->getElementsByTagName('SendDate');
				if($node !== null && $node->length)
				{
					$this->pressDate = $node->item(0)->nodeValue;
					if($format !== null)
					{
						$this->pressDate = HTFunction::convertDate($this->pressDate, $format);
					}
				}
				else
				{
					$this->pressDate = false;
				}
			}
			return $this->pressDate;
		}
		return null;
	}

	/**
	 * Return youth team id
	 *
	 * @return Integer
	 */
	public function getYouthTeamId()
	{
		if(!isset($this->youthTeamId) || $this->youthTeamId === null)
		{
			$this->youthTeamId = $this->getXml()->getElementsByTagName('YouthTeamID')->item(0)->nodeValue;
		}
		if($this->youthTeamId == 0)
		{
			return null;
		}
		return $this->youthTeamId;
	}

	/**
	 * Return number of visits
	 *
	 * @return Integer
	 */
	public function getNumberOfVisits()
	{
		if(!isset($this->numberOfVisits) || $this->numberOfVisits === null)
		{
			$this->numberOfVisits = $this->getXml()->getElementsByTagName('NumberOfVisits')->item(0)->nodeValue;
		}
		return $this->numberOfVisits;
	}
}
class HTEconomy extends HTCommonTeam
{
	private $money = null;
	private $cash = null;
	private $expectedCash = null;
	private $sponsorsPopularityAvailable = null;
	private $sponsorsPopularity = null;
	private $supportersPopularityAvailable = null;
	private $supportersPopularity = null;
	private $fanClubSize = null;
	private $income = null;
	private $costs = null;
	private $expectedWeeksTotal = null;
	private $lastIncome = null;
	private $lastCosts = null;
	private $lastWeekTotal = null;

	/**
	 * @param DOMDocument $xml
	 * @param Integer $money
	 */
	public function __construct($xml, $money = null)
	{
		parent::__construct($xml);
		$this->money = $money;
	}

	/**
	 * Return club cash value
	 *
	 * @return Integer
	 */
	public function getCash()
	{
		if(!isset($this->cash) || $this->cash === null)
		{
			$this->cash = HTMoney::convert($this->getXml()->getElementsByTagName('Cash')->item(0)->nodeValue, $this->money);
		}
		return $this->cash;
	}

	/**
	 * Return club expexted cash value
	 *
	 * @return Integer
	 */
	public function getExpectedCash()
	{
		if(!isset($this->expectedCash) || $this->expectedCash === null)
		{
			$this->expectedCash = HTMoney::convert($this->getXml()->getElementsByTagName('ExpectedCash')->item(0)->nodeValue, $this->money);
		}
		return $this->expectedCash;
	}

	/**
	 * Return sponsors level
	 *
	 * @return Integer
	 */
	public function getSponsorsLevel()
	{
		if(!isset($this->sponsorsPopularityAvailable) || $this->sponsorsPopularityAvailable === null)
		{
			$this->sponsorsPopularityAvailable = strtolower($this->getXml()->getElementsByTagName('SponsorsPopularity')->item(0)->getAttribute('Available')) == 'true';
		}
		if($this->sponsorsPopularityAvailable == true)
		{
			if(!isset($this->sponsorsPopularity) || $this->sponsorsPopularity === null)
			{
				$this->sponsorsPopularity = $this->getXml()->getElementsByTagName('SponsorsPopularity')->item(0)->nodeValue;
			}
			return $this->sponsorsPopularity;
		}
		return null;
	}

	/**
	 * Return supporters level
	 *
	 * @return Integer
	 */
	public function getSupportersLevel()
	{
		if(!isset($this->supportersPopularityAvailable) || $this->supportersPopularityAvailable === null)
		{
			$this->supportersPopularityAvailable = strtolower($this->getXml()->getElementsByTagName('SupportersPopularity')->item(0)->getAttribute('Available')) == 'true';
		}
		if($this->supportersPopularityAvailable == true)
		{
			if(!isset($this->supportersPopularity) || $this->supportersPopularity === null)
			{
				$this->supportersPopularity = $this->getXml()->getElementsByTagName('SupportersPopularity')->item(0)->nodeValue;
			}
			return $this->supportersPopularity;
		}
		return null;
	}

	/**
	 * Return fan club size
	 *
	 * @return Integer
	 */
	public function getFanClubSize()
	{
		if(!isset($this->fanClubSize) || $this->fanClubSize === null)
		{
			$this->fanClubSize = $this->getXml()->getElementsByTagName('FanClubSize')->item(0)->nodeValue;
		}
		return $this->fanClubSize;
	}

	/**
	 * Return income object
	 *
	 * @return HTEconomyIncome
	 */
	public function getIncome()
	{
		if(!isset($this->income) || $this->income === null)
		{
			$this->income = new HTEconomyIncome($this->getXml(), $this->money);
		}
		return $this->income;
	}

	/**
	 * Return costs object
	 *
	 * @return HTEconomyCosts
	 */
	public function getCosts()
	{
		if(!isset($this->costs) || $this->costs === null)
		{
			$this->costs = new HTEconomyCosts($this->getXml(), $this->money);
		}
		return $this->costs;
	}

	/**
	 * Return expected week money
	 *
	 * @return Integer
	 */
	public function getWeekExpected()
	{
		if(!isset($this->expectedWeeksTotal) || $this->expectedWeeksTotal === null)
		{
			$this->expectedWeeksTotal = HTMoney::convert($this->getXml()->getElementsByTagName('ExpectedWeeksTotal')->item(0)->nodeValue, $this->money);
		}
		return $this->expectedWeeksTotal;
	}

	/**
	 * Return last income object
	 *
	 * @return HTEconomyIncomeLast
	 */
	public function getLastIncome()
	{
		if(!isset($this->lastIncome) || $this->lastIncome === null)
		{
			$this->lastIncome = new HTEconomyIncomeLast($this->getXml(), $this->money);
		}
		return $this->lastIncome;
	}

	/**
	 * Return last costs object
	 *
	 * @return HTEconomyCostsLast
	 */
	public function getLastCosts()
	{
		if(!isset($this->lastCosts) || $this->lastCosts === null)
		{
			$this->lastCosts = new HTEconomyCostsLast($this->getXml(), $this->money);
		}
		return $this->lastCosts;
	}

	/**
	 * Return last week total
	 *
	 * @return Integer
	 */
	public function getLastWeekTotal()
	{
		if(!isset($this->lastWeekTotal) || $this->lastWeekTotal === null)
		{
			$this->lastWeekTotal = HTMoney::convert($this->getXml()->getElementsByTagName('LastWeeksTotal')->item(0)->nodeValue, $this->money);
		}
		return $this->lastWeekTotal;
	}
}
class HTEconomyIncome extends HTXml
{
	protected $state;
	protected $money;
	private $spectators = null;
	private $sponsors = null;
	private $financial = null;
	private $temporary = null;
	private $total = null;

	/**
	 * New income object
	 *
	 * @param DOMDocument $xml
	 * @param Integer $money
	 */
	public function __construct($xml, $money = null)
	{
		$this->state = '';
		$this->xmlText = $xml->saveXML();
		$this->xml = $xml;
		$this->money = $money;
	}

	/**
	 * Return spectators income
	 *
	 * @return Integer
	 */
	public function getSpectators()
	{
		if(!isset($this->spectators) || $this->spectators === null)
		{
			$this->spectators = HTMoney::convert($this->getXml()->getElementsByTagName($this->state.'IncomeSpectators')->item(0)->nodeValue, $this->money);
		}
		return $this->spectators;
	}

	/**
	 * Return sponsors income
	 *
	 * @return Integer
	 */
	public function getSponsors()
	{
		if(!isset($this->sponsors) || $this->sponsors === null)
		{
			$this->sponsors = HTMoney::convert($this->getXml()->getElementsByTagName($this->state.'IncomeSponsors')->item(0)->nodeValue, $this->money);
		}
		return $this->sponsors;
	}

	/**
	 * Return financial income
	 *
	 * @return Integer
	 */
	public function getFinancial()
	{
		if(!isset($this->financial) || $this->financial === null)
		{
			$this->financial = HTMoney::convert($this->getXml()->getElementsByTagName($this->state.'IncomeFinancial')->item(0)->nodeValue, $this->money);
		}
		return $this->financial;
	}

	/**
	 * Return temporary income
	 *
	 * @return Integer
	 */
	public function getTemporary()
	{
		if(!isset($this->temporary) || $this->temporary === null)
		{
			$this->temporary = HTMoney::convert($this->getXml()->getElementsByTagName($this->state.'IncomeTemporary')->item(0)->nodeValue, $this->money);
		}
		return $this->temporary;
	}

	/**
	 * Return total income
	 *
	 * @return Integer
	 */
	public function getTotal()
	{
		if(!isset($this->total) || $this->total === null)
		{
			$this->total = HTMoney::convert($this->getXml()->getElementsByTagName($this->state.'IncomeSum')->item(0)->nodeValue, $this->money);
		}
		return $this->total;
	}
}
class HTEconomyIncomeLast extends HTEconomyIncome
{
	/**
	 * @param DOMDocument $xml
	 * @param Integer $money
	 */
	public function __construct($xml, $money = null)
	{
		parent::__construct($xml, $money);
		$this->state = 'Last';
	}
}
class HTEconomyCosts extends HTXml
{
	protected $state;
	protected $money;
	private $arena = null;
	private $players = null;
	private $financial = null;
	private $temporary = null;
	private $staff = null;
	private $youth = null;
	private $total = null;

	/**
	 * New income object
	 *
	 * @param DOMDocument $xml
	 * @param Integer $money
	 */
	public function __construct($xml, $money = null)
	{
		$this->state = '';
		$this->xmlText = $xml->saveXML();
		$this->xml = $xml;
		$this->money = $money;
	}

	/**
	 * Return arena cost
	 *
	 * @return Integer
	 */
	public function getArena()
	{
		if(!isset($this->arena) || $this->arena === null)
		{
			$this->arena = HTMoney::convert($this->getXml()->getElementsByTagName($this->state.'CostsArena')->item(0)->nodeValue, $this->money);
		}
		return $this->arena;
	}

	/**
	 * Return players salaries cost
	 *
	 * @return Integer
	 */
	public function getSalaries()
	{
		if(!isset($this->players) || $this->players === null)
		{
			$this->players = HTMoney::convert($this->getXml()->getElementsByTagName($this->state.'CostsPlayers')->item(0)->nodeValue, $this->money);
		}
		return $this->players;
	}

	/**
	 * Return financial cost
	 *
	 * @return Integer
	 */
	public function getFinancial()
	{
		if(!isset($this->financial) || $this->financial === null)
		{
			$this->financial = HTMoney::convert($this->getXml()->getElementsByTagName($this->state.'CostsFinancial')->item(0)->nodeValue, $this->money);
		}
		return $this->financial;
	}

	/**
	 * Return temporary cost
	 *
	 * @return Integer
	 */
	public function getTemporary()
	{
		if(!isset($this->temporary) || $this->temporary === null)
		{
			$this->temporary = HTMoney::convert($this->getXml()->getElementsByTagName($this->state.'CostsTemporary')->item(0)->nodeValue, $this->money);
		}
		return $this->temporary;
	}

	/**
	 * Return staff cost
	 *
	 * @return Integer
	 */
	public function getStaff()
	{
		if(!isset($this->staff) || $this->staff === null)
		{
			$this->staff = HTMoney::convert($this->getXml()->getElementsByTagName($this->state.'CostsStaff')->item(0)->nodeValue, $this->money);
		}
		return $this->staff;
	}

	/**
	 * Return youth cost
	 *
	 * @return Integer
	 */
	public function getYouth()
	{
		if(!isset($this->youth) || $this->youth === null)
		{
			$this->youth = HTMoney::convert($this->getXml()->getElementsByTagName($this->state.'CostsYouth')->item(0)->nodeValue, $this->money);
		}
		return $this->youth;
	}

	/**
	 * Return total cost
	 *
	 * @return Integer
	 */
	public function getTotal()
	{
		if(!isset($this->total) || $this->total === null)
		{
			$this->total = HTMoney::convert($this->getXml()->getElementsByTagName($this->state.'CostsSum')->item(0)->nodeValue, $this->money);
		}
		return $this->total;
	}
}
class HTEconomyCostsLast extends HTEconomyCosts
{
	/**
	 * @param DOMDocument $xml
	 * @param Integer $money
	 */
	public function __construct($xml, $money = null)
	{
		parent::__construct($xml, $money);
		$this->state = 'Last';
	}
}
class HTMoney
{
	const	AlIraq = 5	;
	const	AlJazair = 0.1 ;
	const	AlKuwayt = 25	;
	const	AlMaghrib = 1	;
	const	AlUrdun = 5	;
	const	AlYaman = 0.1 ;
	const	Andorra = 10	;
	const	Angola = 0.1 ;
	const	Argentina = 10	;
	const	Azerbaycan = 10	;
	const	Bahrain = 20	;
	const	Bangladesh = 0.2 ;
	const	Barbados = 5	;
	const	Belarus = 5	;
	const	Belgie = 10	;
	const	Benin = 10	;
	const	Bolivia = 1	;
	const	BosniaAndHercegovina = 5	;
	const	Brasil = 5	;
	const	Bulgaria = 5	;
	const	CaboVerde = 0.1 ;
	const	Canada = 5	;
	const	CeskaRepublika = 0.25 ;
	const	Chile = 50	;
	const	China = 1	;
	const	ChineseTaipei = 10	;
	const	Colombia = 10	;
	const	CostaRica = 4	;
	const	IvoryCost = 20	;
	const	CrnaGora = 10	;
	const	Cymru = 15	;
	const	Cyprus = 5	;
	const	Danmark = 1	;
	const	Deutschland = 10	;
	const	Ecuador = 10	;
	const	Eesti = 0.5 ;
	const	ElSalvador = 10	;
	const	England = 15	;
	const	Espana = 10	;
	const	Foroyar = 1	;
	const	France = 10	;
	const	Guatemala = 10	;
	const	Hanguk = 10	;
	const	Hayastan = 20	;
	const	Hellas = 10	;
	const	Honduras = 5	;
	const	HongKong = 1	;
	const	Hrvatska = 1	;
	const	India = 0.25 ;
	const	Indonesia = 1	;
	const	Iran = 1	;
	const	Ireland = 10	;
	const	Island = 0.1 ;
	const	Israel = 2	;
	const	Italia = 10	;
	const	Jamaica = 0.5 ;
	const	Kazakhstan = 0.1 ;
	const	Kenya = 0.5 ;
	const	Kyrgyzstan = 0.2 ;
	const	Latvija = 20	;
	const	Letzebuerg = 10	;
	const	Liechtenstein = 5	;
	const	Lietuva = 2.5 ;
	const	Lubnan = 5	;
	const	Magyarorszag = 50	;
	const	Makedonija = 0.5 ;
	const	Malaysia = 2.5 ;
	const	Malta = 10	;
	const	Mexico = 1	;
	const	Misr = 2.5 ;
	const	Mocambique = 0.4 ;
	const	Moldova = 0.5 ;
	const	MongolUls = 5	;
	const	Nederland = 10	;
	const	NegaraBruneiDarussalam = 5	;
	const	Nicaragua = 0.5 ;
	const	Nigeria = 0.1 ;
	const	Nippon = 0.1 ;
	const	Norge = 1	;
	const	NorthernIreland = 15	;
	const	Oceania = 5	;
	const	Osterreich = 10	;
	const	Pakistan = 0.2 ;
	const	Panama = 10	;
	const	Paraguay = 2	;
	const	Peru = 10	;
	const	Philippines = 0.25 ;
	const	Polska = 2.5 ;
	const	Portugal = 10	;
	const	PratehKampuchea = 2.5 ;
	const	PrathetThai = 0.25 ;
	const	RepublicOfGhana = 10	;
	const	RepublicaDominicana = 0.5 ;
	const	Romania = 0.5 ;
	const	Rossiya = 0.25 ;
	const	Sakartvelo = 5	;
	const	SaudiArabia = 2.5 ;
	const	Schweiz = 5	;
	const	Scotland = 15	;
	const	Senegal = 20	;
	const	Shqiperia = 50	;
	const	Singapore = 5	;
	const	Slovenija = 10	;
	const	Slovensko = 0.2 ;
	const	SouthAfrica = 1.25 ;
	const	Srbija = 1	;
	const	Suomi = 10	;
	const	Suriname = 5	;
	const	Suriyah = 10	;
	const	Sverige = 1	;
	const	Tounes = 7	;
	const	TrinidadAndTobago = 1	;
	const	Turkiye = 10	;
	const	Ukraina = 2	;
	const	Uman = 20	;
	const	UnitedArabEmirates = 4	;
	const	Uruguay = 1	;
	const	USA = 10	;
	const	Venezuela = 10	;
	const	Vietnam = 1	;

	/**
	 * Convert a money amount into country currency
	 *
	 * @param Integer $amount
	 * @param Integer $country
	 * @return Integer
	 */
	public static function convert($amount, $country)
	{
		if($country !== null)
		{
			return round($amount / $country);
		}
		return $amount;
	}
}
class HTRegion extends HTCommonLeague
{
	private $regionId = null;
	private $regionName = null;
	private $weatherId = null;
	private $tomorrowWheaterId = null;
	private $numberOfUsers = null;
	private $numberOfOnline = null;

	/**
	 * Return region id
	 *
	 * @return Integer
	 */
	public function getId()
	{
		if(!isset($this->regionId) || $this->regionId === null)
		{
			$this->regionId = $this->getXml()->getElementsByTagName('RegionID')->item(0)->nodeValue;
		}
		return $this->regionId;
	}

	/**
	 * Return region name
	 *
	 * @return String
	 */
	public function getName()
	{
		if(!isset($this->regionName) || $this->regionName === null)
		{
			$this->regionName = $this->getXml()->getElementsByTagName('RegionID')->item(0)->nodeValue;
		}
		return $this->regionName;
	}

	/**
	 * Return current weather id
	 *
	 * @return Integer
	 */
	public function getWeatherId()
	{
		if(!isset($this->weatherId) || $this->weatherId === null)
		{
			$this->weatherId = $this->getXml()->getElementsByTagName('WeatherID')->item(0)->nodeValue;
		}
		return $this->weatherId;
	}

	/**
	 * Return tomorrow weather id
	 *
	 * @return Integer
	 */
	public function getTomorrowWeatherId()
	{
		if(!isset($this->tomorrowWheaterId) || $this->tomorrowWheaterId === null)
		{
			$this->tomorrowWheaterId = $this->getXml()->getElementsByTagName('TomorrowWeatherID')->item(0)->nodeValue;
		}
		return $this->tomorrowWheaterId;
	}

	/**
	 * Return number of users
	 *
	 * @return Integer
	 */
	public function getUserNumber()
	{
		if(!isset($this->numberOfUsers) || $this->numberOfUsers === null)
		{
			$this->numberOfUsers = $this->getXml()->getElementsByTagName('NumberOfUsers')->item(0)->nodeValue;
		}
		return $this->numberOfUsers;
	}

	/**
	 * Return number of online users
	 *
	 * @return Integer
	 */
	public function getOnlineUserNumber()
	{
		if(!isset($this->numberOfOnline) || $this->numberOfOnline === null)
		{
			$this->numberOfOnline = $this->getXml()->getElementsByTagName('NumberOfOnline')->item(0)->nodeValue;
		}
		return $this->numberOfOnline;
	}
}
class HTLeague extends HTCommonLeagueLevel
{
	private $leagueLevel = null;
	private $maxLevel = null;
	private $leagueId = null;
	private $leagueName = null;
	private $teams = array();

	/**
	 * Return league level
	 *
	 * @return Integer
	 */
	public function getLeagueLevel()
	{
		if(!isset($this->leagueLevel) || $this->leagueLevel === null)
		{
			$this->leagueLevel = $this->getXml()->getElementsByTagName('LeagueLevel')->item(0)->nodeValue;
		}
		return $this->leagueLevel;
	}

	/**
	 * Return max level
	 *
	 * @return Integer
	 */
	public function getMaxLevel()
	{
		if(!isset($this->maxLevel) || $this->maxLevel === null)
		{
			$this->maxLevel = $this->getXml()->getElementsByTagName('MaxLevel')->item(0)->nodeValue;
		}
		return $this->maxLevel;
	}

	/**
	 * Return league id
	 *
	 * @return Integer
	 */
	public function getLeagueId()
	{
		if(!isset($this->leagueId) || $this->leagueId === null)
		{
			$this->leagueId = $this->getXml()->getElementsByTagName('LeagueID')->item(0)->nodeValue;
		}
		return $this->leagueId;
	}

	/**
	 * Return league name
	 *
	 * @return String
	 */
	public function getLeagueName()
	{
		if(!isset($this->leagueName) || $this->leagueName === null)
		{
			$this->leagueName = $this->getXml()->getElementsByTagName('LeagueName')->item(0)->nodeValue;
		}
		return $this->leagueName;
	}

	/**
	 * Return HTLeagueTeam object
	 *
	 * @param Integer $position
	 * @return HTLeagueTeam
	 */
	public function getTeam($position)
	{
		$position = round($position);
		if($position > 0 && $position < 9)
		{
			if(!isset($this->teams[$position]) || $this->teams[$position] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query("//Position[.='".$position."']");
				$node = new DOMDocument('1.0', 'UTF-8');
				$node->appendChild($node->importNode($nodeList->item(0)->parentNode, true));
				$this->teams[$position] = new HTLeagueTeam($node);
			}
			return $this->teams[$position];
		}
		return null;
	}
}
class HTLeagueTeam extends HTXml
{
	private $teamId = null;
	private $position = null;
	private $hasPostionChange = null;
	private $postionChange = null;
	private $teamName = null;
	private $numberPlayedMatches = null;
	private $goalsFor = null;
	private $goalsAgainst = null;
	private $points = null;

	/**
	 * @param DOMDocument $fullxml
	 * @param Integer $index
	 */
	public function __construct($xml)
	{
		$this->xmlText = $xml->saveXML();
		$this->xml = $xml;
	}

	/**
	 * Return team id
	 *
	 * @return Integer
	 */
	public function getTeamId()
	{
		if(!isset($this->teamId) || $this->teamId === null)
		{
			$this->teamId = $this->getXml()->getElementsByTagName('TeamID')->item(0)->nodeValue;
		}
		return $this->teamId;
	}

	/**
	 * Return team name
	 *
	 * @return String
	 */
	public function getTeamName()
	{
		if(!isset($this->teamName) || $this->teamName === null)
		{
			$this->teamName = $this->getXml()->getElementsByTagName('TeamName')->item(0)->nodeValue;
		}
		return $this->teamName;
	}

	/**
	 * Return position
	 *
	 * @return Integer
	 */
	public function getPosition()
	{
		if(!isset($this->position) || $this->position === null)
		{
			$this->position = $this->getXml()->getElementsByTagName('Position')->item(0)->nodeValue;
		}
		return $this->position;
	}

	/**
	 * Return if position change is available
	 *
	 * @return Boolean
	 */
	public function isPositionChangeAvailable()
	{
		if(!isset($this->hasPostionChange) || $this->hasPostionChange === null)
		{
			$this->hasPostionChange = $this->getXml()->getElementsByTagName('PositionChange') !== null;
		}
		return $this->hasPostionChange;
	}

	/**
	 * Return position change
	 *
	 * @return Integer
	 */
	public function getPositionChange()
	{
		if($this->isPositionChangeAvailable())
		{
			if(!isset($this->postionChange) || $this->postionChange === null)
			{
				$this->postionChange = $this->getXml()->getElementsByTagName('PositionChange')->item(0)->nodeValue;
			}
			return $this->postionChange;
		}
		return null;
	}

	/**
	 * Return number of played matches
	 *
	 * @return Integer
	 */
	public function getNumberOfPlayedMatches()
	{
		if(!isset($this->numberPlayedMatches) || $this->numberPlayedMatches === null)
		{
			$this->numberPlayedMatches = $this->getXml()->getElementsByTagName('Matches')->item(0)->nodeValue;
		}
		return $this->numberPlayedMatches;
	}

	/**
	 * Return number of goal for team
	 *
	 * @return Integer
	 */
	public function getGoalsFor()
	{
		if(!isset($this->goalsFor) || $this->goalsFor === null)
		{
			$this->goalsFor = $this->getXml()->getElementsByTagName('GoalsFor')->item(0)->nodeValue;
		}
		return $this->goalsFor;
	}

	/**
	 * Return number of goal against team
	 *
	 * @return Integer
	 */
	public function getGoalsAgainst()
	{
		if(!isset($this->goalsAgainst) || $this->goalsAgainst === null)
		{
			$this->goalsAgainst = $this->getXml()->getElementsByTagName('GoalsAgainst')->item(0)->nodeValue;
		}
		return $this->goalsAgainst;
	}

	/**
	 * Return total points
	 *
	 * @return Integer
	 */
	public function getPoints()
	{
		if(!isset($this->points) || $this->points === null)
		{
			$this->points = $this->getXml()->getElementsByTagName('Points')->item(0)->nodeValue;
		}
		return $this->points;
	}

	/**
	 * Return goal average
	 *
	 * @return Integer
	 */
	public function getGoalAverage()
	{
		return $this->getGoalsFor() - $this->getGoalsAgainst();
	}
}
class HTLeagueSeason extends HTCommonLeagueLevel
{
	private $season = null;
	private $rounds = array();

	/**
	 * Return season number
	 *
	 * @return Integer
	 */
	public function getSeason()
	{
		if(!isset($this->season) || $this->season === null)
		{
			$this->season = $this->getXml()->getElementsByTagName('Season')->item(0)->nodeValue;
		}
		return $this->season;
	}

	/**
	 * Return HTLeagueRound object
	 *
	 * @param Integer $id
	 * @return HTLeagueRound
	 */
	public function getRound($id)
	{
		$id = round($id);
		if($id > 0 && $id < 15)
		{
			if(!isset($this->rounds[$id]) || $this->rounds[$id] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query("//MatchRound[.='".$id."']");
				$matches = new DOMDocument('1.0', 'UTF-8');
				foreach ($nodeList as $node)
				{
					$matches->appendChild($matches->importNode($node->parentNode, true));
				}
				$this->rounds[$id] = new HTLeagueRound($matches);
			}
			return $this->rounds[$id];
		}
		return null;
	}

	/**
	 * Return total goals of the season
	 *
	 * @return Integer
	 */
	public function getTotalGoals()
	{
		$total = 0;
		for($i=1; $i<15; $i++)
		{
			$total += $this->getRound($i)->getTotalGoals();
		}
		return $total;
	}
}
class HTLeagueRound extends HTXml
{
	private $matches = array();

	/**
	 * @param DOMDocument $xml
	 */
	public function __construct($xml)
	{
		$this->xmlText = $xml->saveXML();
		$this->xml = $xml;
	}

	/**
	 * Return HTLeagueMatch object
	 *
	 * @param Integer $index
	 * @return HTLeagueMatch
	 */
	public function getMatch($index)
	{
		$index = round($index);
		if($index > 0 && $index < 5)
		{
			--$index;
			if(!isset($this->matches[$index]) || $this->matches[$index] === null)
			{
				$match = new DOMDocument('1.0', 'UTF-8');
				$match->appendChild($match->importNode($this->getXml()->getElementsByTagName('Match')->item($index), true));
				$this->matches[$index] = new HTLeagueMatch($match);
			}
			return $this->matches[$index];
		}
		return null;
	}

	/**
	 * Return total goal of the round
	 *
	 * @return Integer
	 */
	public function getTotalGoals()
	{
		$total = 0;
		for($i=1; $i<5; $i++)
		{
			$total += $this->getMatch($i)->getTotalGoals();
		}
		return $total;
	}
}
class HTLeagueMatch extends HTXml
{
	private $matchId = null;
	private $matchRound = null;
	private $homeTeamId = null;
	private $homeTeamName = null;
	private $awayTeamId = null;
	private $awayTeamName = null;
	private $matchDate = null;
	private $homeGoals = null;
	private $awayGoals = null;
	const UNAVAILABLE = 'unavailable';

	/**
	 * @param DOMDocument $xml
	 */
	public function __construct($xml)
	{
		$this->xmlText = $xml->saveXML();
		$this->xml = $xml;
	}

	/**
	 * Return match id
	 *
	 * @return Integer
	 */
	public function getMatchId()
	{
		if(!isset($this->matchId) || $this->matchId === null)
		{
			$this->matchId = $this->getXml()->getElementsByTagName('MatchID')->item(0)->nodeValue;
		}
		return $this->matchId;
	}

	/**
	 * Return match round
	 *
	 * @return Integer
	 */
	public function getMatchRound()
	{
		if(!isset($this->matchRound) || $this->matchRound === null)
		{
			$this->matchRound = $this->getXml()->getElementsByTagName('MatchRound')->item(0)->nodeValue;
		}
		return $this->matchRound;
	}

	/**
	 * Return match date
	 *
	 * @param String $format (php date() function format)
	 * @return String
	 */
	public function getDate($format = null)
	{
		if(!isset($this->matchDate) || $this->matchDate === null)
		{
			$this->matchDate = $this->getXml()->getElementsByTagName('MatchDate')->item(0)->nodeValue;
			if($format !== null)
			{
				$this->matchDate = HTFunction::convertDate($this->matchDate, $format);
			}
		}
		return $this->matchDate;
	}

	/**
	 * Return home team id
	 *
	 * @return Integer
	 */
	public function getHomeTeamId()
	{
		if(!isset($this->homeTeamId) || $this->homeTeamId === null)
		{
			$this->homeTeamId = $this->getXml()->getElementsByTagName('HomeTeamID')->item(0)->nodeValue;
		}
		return $this->homeTeamId;
	}

	/**
	 * Return home team name
	 *
	 * @return String
	 */
	public function getHomeTeamName()
	{
		if(!isset($this->homeTeamName) || $this->homeTeamName === null)
		{
			$this->homeTeamName = $this->getXml()->getElementsByTagName('HomeTeamName')->item(0)->nodeValue;
		}
		return $this->homeTeamName;
	}

	/**
	 * Return away team id
	 *
	 * @return Integer
	 */
	public function getAwayTeamId()
	{
		if(!isset($this->awayTeamId) || $this->awayTeamId === null)
		{
			$this->awayTeamId = $this->getXml()->getElementsByTagName('AwayTeamID')->item(0)->nodeValue;
		}
		return $this->awayTeamId;
	}

	/**
	 * Return away team name
	 *
	 * @return String
	 */
	public function getAwayTeamName()
	{
		if(!isset($this->awayTeamName) || $this->awayTeamName === null)
		{
			$this->awayTeamName = $this->getXml()->getElementsByTagName('AwayTeamName')->item(0)->nodeValue;
		}
		return $this->awayTeamName;
	}

	/**
	 * Return number of home goals
	 *
	 * @return Integer
	 */
	public function getHomeGoals()
	{
		if(!isset($this->homeGoals) || $this->homeGoals === null)
		{
			$node = $this->getXml()->getElementsByTagName('HomeGoals');
			if($node !== null && $node->length)
			{
				$this->homeGoals = $node->item(0)->nodeValue;
			}
			else
			{
				$this->homeGoals = self::UNAVAILABLE ;
			}

		}
		if($this->homeGoals === self::UNAVAILABLE )
		{
			return null;
		}
		return $this->homeGoals;
	}

	/**
	 * Return number of away goals
	 *
	 * @return Integer
	 */
	public function getAwayGoals()
	{
		if(!isset($this->awayGoals) || $this->awayGoals === null)
		{
			$node = $this->getXml()->getElementsByTagName('AwayGoals');
			if($node !== null && $node->length)
			{
				$this->awayGoals = $node->item(0)->nodeValue;
			}
			else
			{
				$this->awayGoals = self::UNAVAILABLE ;
			}

		}
		if($this->awayGoals === self::UNAVAILABLE )
		{
			return null;
		}
		return $this->awayGoals;
	}

	/**
	 * Return total goal of the match
	 *
	 * @return Integer
	 */
	public function getTotalGoals()
	{
		return $this->getHomeGoals() + $this->getAwayGoals();
	}
}
class HTWorldDetails extends HTGlobal
{
	private $leagueCount = null;
	private $leagues = array();
	private $leaguesById = array();
	private $leaguesByCountryId = array();
	private $leaguesByName = array();

	/**
	 * @param DOMDocument $xml
	 */
	public function __construct($xml)
	{
		parent::__construct($xml);
	}

	/**
	 * Return number of league
	 *
	 * @return Integer
	 */
	public function getTotalLeague()
	{
		if(!isset($this->leagueCount) || $this->leagueCount === null)
		{
			$this->leagueCount = $this->getXml()->getElementsByTagName('League')->length;
		}
		return $this->leagueCount;
	}

	/**
	 * Return HTWorldLeague object
	 *
	 * @param Integer $number
	 * @return HTWorldLeague
	 */
	public function getLeague($number)
	{
		$number = round($number);
		if($number > 0 && $number <= $this->getTotalLeague())
		{
			--$number;
			if(!isset($this->leagues[$number]) || $this->leagues[$number] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//League');
				$league = new DOMDocument('1.0', 'UTF-8');
				$league->appendChild($league->importNode($nodeList->item($number), true));
				$this->leagues[$number] = new HTWorldLeague($league);
			}
			return $this->leagues[$number];
		}
		return null;
	}

	/**
	 * Return HTWorldLeague object
	 *
	 * @param Integer $leagueId
	 * @return HTWorldLeague
	 */
	public function getLeagueById($leagueId)
	{
		if(!isset($this->leaguesById[$leagueId]) || $this->leaguesById[$leagueId] === null)
		{
			$xpath = new DOMXPath($this->getXml());
			$nodeList = $xpath->query('//LeagueID[.="'.$leagueId.'"]');
			$league = new DOMDocument('1.0', 'UTF-8');
			$league->appendChild($league->importNode($nodeList->item(0)->parentNode, true));
			$this->leaguesById[$leagueId] = new HTWorldLeague($league);
		}
		return $this->leaguesById[$leagueId];
	}

	/**
	 * Return HTWorldLeague object
	 *
	 * @param Integer $leagueId
	 * @return HTWorldLeague
	 */
	public function getLeagueByCountryId($countryId)
	{
		if(!isset($this->leaguesByCountryId[$countryId]) || $this->leaguesByCountryId[$countryId] === null)
		{
			$xpath = new DOMXPath($this->getXml());
			$nodeList = $xpath->query('//CountryID[.="'.$countryId.'"]');
			$league = new DOMDocument('1.0', 'UTF-8');
			$league->appendChild($league->importNode($nodeList->item(0)->parentNode->parentNode, true));
			$this->leaguesByCountryId[$countryId] = new HTWorldLeague($league);
		}
		return $this->leaguesByCountryId[$countryId];
	}

	/**
	 * Return HTWorldLeague object
	 *
	 * @param String $leagueName
	 * @return HTWorldLeague
	 */
	public function getLeagueByName($leagueName)
	{
		if(!isset($this->leaguesByName[$leagueName]) || $this->leaguesByName[$leagueName] === null)
		{
			$xpath = new DOMXPath($this->getXml());
			$nodeList = $xpath->query('//LeagueName[.="'.$leagueName.'"]');
			$league = new DOMDocument('1.0', 'UTF-8');
			$league->appendChild($league->importNode($nodeList->item(0)->parentNode, true));
			$this->leaguesByName[$leagueName] = new HTWorldLeague($league);
		}
		return $this->leaguesByName[$leagueName];
	}
}
class HTWorldLanguages extends HTGlobal
{
	private $langueCount = null;
	private $langues = array();

	/**
	 * @param DOMDocument $xml
	 */
	public function __construct($xml)
	{
		parent::__construct($xml);
	}
	/**
	 * Return number of languages
	 *
	 * @return Integer
	 */
	public function getTotalLanguages()
	{
		if(!isset($this->langueCount) || $this->langueCount === null)
		{
			$this->langueCount = $this->getXml()->getElementsByTagName('Language')->length;
		}
		return $this->langueCount;
	}

	/**
	 * Return HTWorldLanguage object
	 *
	 * @param Integer $number
	 * @return HTWorldLanguage
	 */
	public function getLanguage($number)
	{
		$number = round($number);
		if($number > 0 && $number <= $this->getTotalLanguages())
		{
			--$number;
			if(!isset($this->langues[$number]) || $this->langues[$number] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//Language');
				$langue = new DOMDocument('1.0', 'UTF-8');
				$langue->appendChild($langue->importNode($nodeList->item($number), true));
				$this->langues[$number] = new HTWorldLanguage($langue);
			}
			return $this->langues[$number];
		}
		return null;
	}
}
class HTWorldLanguage extends HTXml
{
	private $id = null;
	private $name = null;

	/**
	 * @param DOMDocument $xml
	 */
	public function __construct($xml)
	{
		$this->xmlText = $xml->saveXML();
		$this->xml = $xml;
	}

	/**
	 * Return language id
	 *
	 * @return Integer
	 */
	public function getId()
	{
		if(!isset($this->id) || $this->id === null)
		{
			$this->id = $this->getXml()->getElementsByTagName('LanguageID')->item(0)->nodeValue;
		}
		return $this->id;
	}

	/**
	 * Return language name
	 *
	 * @return String
	 */
	public function getName()
	{
		if(!isset($this->name) || $this->name === null)
		{
			$this->name = $this->getXml()->getElementsByTagName('LanguageName')->item(0)->nodeValue;
		}
		return $this->name;
	}
}
class HTWorldLeague extends HTXml
{
	private $id = null;
	private $name = null;
	private $season = null;
	private $matchRound = null;
	private $shortName = null;
	private $continent = null;
	private $zoneName = null;
	private $englishName = null;
	private $countryId = null;
	private $countryName = null;
	private $currencyName = null;
	private $currencyRate = null;
	private $cupId = null;
	private $cupName = null;
	private $numberActiveUsers = null;
	private $numberWaitingUsers = null;
	private $trainingDate = null;
	private $economyDate = null;
	private $cupMatchDate = null;
	private $seriesMatchDate = null;
	private $numberLevel = null;

	/**
	 * @param DOMDocument $xml
	 */
	public function __construct($xml)
	{
		$this->xmlText = $xml->saveXML();
		$this->xml = $xml;
	}

	/**
	 * Return league id
	 *
	 * @return Integer
	 */
	public function getId()
	{
		if(!isset($this->id) || $this->id === null)
		{
			$this->id = $this->getXml()->getElementsByTagName('LeagueID')->item(0)->nodeValue;
		}
		return $this->id;
	}

	/**
	 * Return league name
	 *
	 * @return String
	 */
	public function getName()
	{
		if(!isset($this->name) || $this->name === null)
		{
			$this->name = $this->getXml()->getElementsByTagName('LeagueName')->item(0)->nodeValue;
		}
		return $this->name;
	}

	/**
	 * Return current season number
	 *
	 * @return Integer
	 */
	public function getSeasonNumber()
	{
		if(!isset($this->season) || $this->season === null)
		{
			$this->season = $this->getXml()->getElementsByTagName('Season')->item(0)->nodeValue;
		}
		return $this->season;
	}

	/**
	 * Return match round number
	 *
	 * @return Integer
	 */
	public function getMatchRound()
	{
		if(!isset($this->matchRound) || $this->matchRound === null)
		{
			$this->matchRound = $this->getXml()->getElementsByTagName('MatchRound')->item(0)->nodeValue;
		}
		return $this->matchRound;
	}

	/**
	 * Return league short name
	 *
	 * @return String
	 */
	public function getShortName()
	{
		if(!isset($this->shortName) || $this->shortName === null)
		{
			$this->shortName = $this->getXml()->getElementsByTagName('ShortName')->item(0)->nodeValue;
		}
		return $this->shortName;
	}

	/**
	 * Return continent
	 *
	 * @return String
	 */
	public function getContinent()
	{
		if(!isset($this->continent) || $this->continent === null)
		{
			$this->continent = $this->getXml()->getElementsByTagName('Continent')->item(0)->nodeValue;
		}
		return $this->continent;
	}

	/**
	 * Return world zone
	 *
	 * @return String
	 */
	public function getZoneName()
	{
		if(!isset($this->zoneName) || $this->zoneName === null)
		{
			$this->zoneName = $this->getXml()->getElementsByTagName('ZoneName')->item(0)->nodeValue;
		}
		return $this->zoneName;
	}

	/**
	 * Return english name
	 *
	 * @return String
	 */
	public function getEnglishName()
	{
		if(!isset($this->englishName) || $this->englishName === null)
		{
			$this->englishName = $this->getXml()->getElementsByTagName('EnglishName')->item(0)->nodeValue;
		}
		return $this->englishName;
	}

	/**
	 * Return country id
	 *
	 * @return Integer
	 */
	public function getCountryId()
	{
		if(!isset($this->countryId) || $this->countryId === null)
		{
			$this->countryId = $this->getXml()->getElementsByTagName('CountryID')->item(0)->nodeValue;
		}
		return $this->countryId;
	}

	/**
	 * Return country name
	 *
	 * @return String
	 */
	public function getCountryName()
	{
		if(!isset($this->countryName) || $this->countryName === null)
		{
			$this->countryName = $this->getXml()->getElementsByTagName('CountryName')->item(0)->nodeValue;
		}
		return $this->countryName;
	}

	/**
	 * Return currency name
	 *
	 * @return String
	 */
	public function getCurrencyName()
	{
		if(!isset($this->currencyName) || $this->currencyName === null)
		{
			$this->currencyName = $this->getXml()->getElementsByTagName('CurrencyName')->item(0)->nodeValue;
		}
		return $this->currencyName;
	}

	/**
	 * Return currency rate
	 *
	 * @return Integer
	 */
	public function getCurrencyRate()
	{
		if(!isset($this->currencyRate) || $this->currencyRate === null)
		{
			$this->currencyRate = $this->getXml()->getElementsByTagName('CurrencyRate')->item(0)->nodeValue;
		}
		return $this->currencyRate;
	}

	/**
	 * Return cup id
	 *
	 * @return Integer
	 */
	public function getCupId()
	{
		if(!isset($this->cupId) || $this->cupId === null)
		{
			$this->cupId = $this->getXml()->getElementsByTagName('CupID')->item(0)->nodeValue;
		}
		return $this->cupId;
	}

	/**
	 * Return cup name
	 *
	 * @return String
	 */
	public function getCupName()
	{
		if(!isset($this->cupName) || $this->cupName === null)
		{
			$this->cupName = $this->getXml()->getElementsByTagName('CupName')->item(0)->nodeValue;
		}
		return $this->cupName;
	}

	/**
	 * Return number of active users
	 *
	 * @return Integer
	 */
	public function getNumberActiveUsers()
	{
		if(!isset($this->numberActiveUsers) || $this->numberActiveUsers === null)
		{
			$this->numberActiveUsers = $this->getXml()->getElementsByTagName('ActiveUsers')->item(0)->nodeValue;
		}
		return $this->numberActiveUsers;
	}

	/**
	 * Return number of waiting users
	 *
	 * @return Integer
	 */
	public function getNumberWaitingUsers()
	{
		if(!isset($this->numberWaitingUsers) || $this->numberWaitingUsers === null)
		{
			$this->numberWaitingUsers = $this->getXml()->getElementsByTagName('WaitingUsers')->item(0)->nodeValue;
		}
		return $this->numberWaitingUsers;
	}

	/**
	 * Return training update date
	 *
	 * @param String $format (php date() function format)
	 * @return String
	 */
	public function getTrainingDate($format = null)
	{
		if(!isset($this->trainingDate) || $this->trainingDate === null)
		{
			$this->trainingDate = $this->getXml()->getElementsByTagName('TrainingDate')->item(0)->nodeValue;
			if($format !== null)
			{
				$this->trainingDate = HTFunction::convertDate($this->trainingDate, $format);
			}
		}
		return $this->trainingDate;
	}

	/**
	 * Return economy update date
	 *
	 * @param String $format (php date() function format)
	 * @return String
	 */
	public function getEconomyDate($format = null)
	{
		if(!isset($this->economyDate) || $this->economyDate === null)
		{
			$this->economyDate = $this->getXml()->getElementsByTagName('EconomyDate')->item(0)->nodeValue;
			if($format !== null)
			{
				$this->economyDate = HTFunction::convertDate($this->economyDate, $format);
			}
		}
		return $this->economyDate;
	}

	/**
	 * Return cup match date
	 *
	 * @param String $format (php date() function format)
	 * @return String
	 */
	public function getCupMatchDate($format = null)
	{
		if(!isset($this->cupMatchDate) || $this->cupMatchDate === null)
		{
			$this->cupMatchDate = $this->getXml()->getElementsByTagName('CupMatchDate')->item(0)->nodeValue;
			if($format !== null)
			{
				$this->cupMatchDate = HTFunction::convertDate($this->cupMatchDate, $format);
			}
		}
		return $this->cupMatchDate;
	}

	/**
	 * Return series match date
	 *
	 * @param String $format (php date() function format)
	 * @return String
	 */
	public function getSeriesMatchDate($format = null)
	{
		if(!isset($this->seriesMatchDate) || $this->seriesMatchDate === null)
		{
			$this->seriesMatchDate = $this->getXml()->getElementsByTagName('SeriesMatchDate')->item(0)->nodeValue;
			if($format !== null)
			{
				$this->seriesMatchDate = HTFunction::convertDate($this->seriesMatchDate, $format);
			}
		}
		return $this->cupMatchDate;
	}

	/**
	 * Return max league level
	 *
	 * @return Integer
	 */
	public function getNumberLevel()
	{
		if(!isset($this->numberLevel) || $this->numberLevel === null)
		{
			$this->numberLevel = $this->getXml()->getElementsByTagName('NumberOfLevels')->item(0)->nodeValue;
		}
		return $this->numberLevel;
	}
}
class HTTraining extends HTCommonSubscriber
{
	private $trainingLevel = null;
	private $newTrainingLevelAvailable = null;
	private $newTrainingLevel = null;
	private $trainingType = null;
	private $staminaTrainingPart = null;
	private $trainerId = null;
	private $trainerName = null;
	private $trainerArrivalDate = null;
	private $moraleAvailable = null;
	private $morale = null;
	private $selfConfidenceAvailable = null;
	private $selfConfidence = null;
	private $exprience433 = null;
	private $exprience451 = null;
	private $exprience352 = null;
	private $exprience532 = null;
	private $exprience343 = null;
	private $exprience541 = null;

	/**
	 * Return training level
	 *
	 * @return Integer
	 */
	public function getTrainingLevel()
	{
		if(!isset($this->trainingLevel) || $this->trainingLevel === null)
		{
			$this->trainingLevel = $this->getXml()->getElementsByTagName('TrainingLevel')->item(0)->nodeValue;
		}
		return $this->trainingLevel;
	}

	/**
	 * Return new training level if available
	 *
	 * @return Integer
	 */
	public function getNewTrainingLevel()
	{
		if(!isset($this->newTrainingLevelAvailable) || $this->newTrainingLevelAvailable === null)
		{
			$this->supportersPopularityAvailable = $this->getXml()->getElementsByTagName('NewTrainingLevel')->item(0)->getAttribute('Available');
		}
		if($this->newTrainingLevelAvailable == true)
		{
			if(!isset($this->newTrainingLevel) || $this->newTrainingLevel === null)
			{
				$this->newTrainingLevel = $this->getXml()->getElementsByTagName('NewTrainingLevel')->item(0)->nodeValue;
			}
			return $this->newTrainingLevel;
		}
		return null;
	}

	/**
	 * Return training type
	 *
	 * @return Integer
	 */
	public function getTrainingType()
	{
		if(!isset($this->trainingType) || $this->trainingType === null)
		{
			$this->trainingType = $this->getXml()->getElementsByTagName('TrainingType')->item(0)->nodeValue;
		}
		return $this->trainingType;
	}

	/**
	 * Return training stamina part
	 *
	 * @return Integer
	 */
	public function getStaminaTrainingPart()
	{
		if(!isset($this->staminaTrainingPart) || $this->staminaTrainingPart === null)
		{
			$this->staminaTrainingPart = $this->getXml()->getElementsByTagName('StaminaTrainingPart')->item(0)->nodeValue;
		}
		return $this->staminaTrainingPart;
	}

	/**
	 * Return trainer id
	 *
	 * @return Integer
	 */
	public function getTrainerId()
	{
		if(!isset($this->trainerId) || $this->trainerId === null)
		{
			$this->trainerId = $this->getXml()->getElementsByTagName('TrainerID')->item(0)->nodeValue;
		}
		return $this->trainerId;
	}

	/**
	 * Return trainer name
	 *
	 * @return String
	 */
	public function getTrainerName()
	{
		if(!isset($this->trainerName) || $this->trainerName === null)
		{
			$this->trainerName = $this->getXml()->getElementsByTagName('TrainerName')->item(0)->nodeValue;
		}
		return $this->trainerName;
	}

	/**
	 * Return trainer arrival date
	 *
	 * @param String $format (php date() function format)
	 * @return String
	 */
	public function getTrainerArrivalDate($format = null)
	{
		if(!isset($this->trainerArrivalDate) || $this->trainerArrivalDate === null)
		{
			$this->trainerArrivalDate = $this->getXml()->getElementsByTagName('ArrivalDate')->item(0)->nodeValue;
			if($format !== null)
			{
				$this->trainerArrivalDate = HTFunction::convertDate($this->trainerArrivalDate, $format);
			}
		}
		return $this->trainerArrivalDate;
	}

	/**
	 * Return team spirit level
	 *
	 * @return Integer
	 */
	public function getTeamSpirit()
	{
		if(!isset($this->moraleAvailable) || $this->moraleAvailable === null)
		{
			$this->moraleAvailable = strtolower($this->getXml()->getElementsByTagName('Morale')->item(0)->getAttribute('Available')) == "true";
		}
		if($this->moraleAvailable == true)
		{
			if(!isset($this->morale) || $this->morale === null)
			{
				$this->morale = $this->getXml()->getElementsByTagName('Morale')->item(0)->nodeValue;
			}
			return $this->morale;
		}
		return null;
	}

	/**
	 * Return self confidence level
	 *
	 * @return Integer
	 */
	public function getSelfConfidence()
	{
		if(!isset($this->selfConfidenceAvailable) || $this->selfConfidenceAvailable === null)
		{
			$this->selfConfidenceAvailable = strtolower($this->getXml()->getElementsByTagName('SelfConfidence')->item(0)->getAttribute('Available')) == "true";
		}
		if($this->selfConfidenceAvailable == true)
		{
			if(!isset($this->selfConfidence) || $this->selfConfidence === null)
			{
				$this->selfConfidence = $this->getXml()->getElementsByTagName('SelfConfidence')->item(0)->nodeValue;
			}
			return $this->selfConfidence;
		}
		return null;
	}

	/**
	 * Return 343 experience level
	 *
	 * @return Integer
	 */
	public function get433Experience()
	{
		if(!isset($this->exprience343) || $this->exprience343 === null)
		{
			$this->exprience433 = $this->getXml()->getElementsByTagName('Experience433')->item(0)->nodeValue;
		}
		return $this->exprience433;
	}

	/**
	 * Return 451 experience level
	 *
	 * @return Integer
	 */
	public function get451Experience()
	{
		if(!isset($this->exprience451) || $this->exprience451 === null)
		{
			$this->exprience451 = $this->getXml()->getElementsByTagName('Experience451')->item(0)->nodeValue;
		}
		return $this->exprience451;
	}

	/**
	 * Return 352 experience level
	 *
	 * @return Integer
	 */
	public function get352Experience()
	{
		if(!isset($this->exprience352) || $this->exprience352 === null)
		{
			$this->exprience352 = $this->getXml()->getElementsByTagName('Experience352')->item(0)->nodeValue;
		}
		return $this->exprience352;
	}

	/**
	 * Return 532 experience level
	 *
	 * @return Integer
	 */
	public function get532Experience()
	{
		if(!isset($this->exprience532) || $this->exprience532 === null)
		{
			$this->exprience532 = $this->getXml()->getElementsByTagName('Experience532')->item(0)->nodeValue;
		}
		return $this->exprience532;
	}

	/**
	 * Return 343 experience level
	 *
	 * @return Integer
	 */
	public function get343Experience()
	{
		if(!isset($this->exprience343) || $this->exprience343 === null)
		{
			$this->exprience343 = $this->getXml()->getElementsByTagName('Experience343')->item(0)->nodeValue;
		}
		return $this->exprience343;
	}

	/**
	 * Return 541 experience level
	 *
	 * @return Integer
	 */
	public function get541Experience()
	{
		if(!isset($this->exprience541) || $this->exprience541 === null)
		{
			$this->exprience541 = $this->getXml()->getElementsByTagName('Experience541')->item(0)->nodeValue;
		}
		return $this->exprience541;
	}
}
class HTTrainingStats extends HTGlobal
{
	private $leagueId = null;
	private $numberTrainingType = null;
	private $trainingType = array();

	/**
	 * Return league id
	 *
	 * @return Integer
	 */
	public function getLeagueId()
	{
		if(!isset($this->leagueId) || $this->leagueId === null)
		{
			$this->leagueId = $this->getXml()->getElementsByTagName('LeagueID')->item(0)->nodeValue;
		}
		return $this->leagueId;
	}

	/**
	 * Return number of training types
	 *
	 * @return Integer
	 */
	public function getNumberTrainingType()
	{
		if(!isset($this->numberTrainingType) || $this->numberTrainingType === null)
		{
			$this->numberTrainingType = $this->getXml()->getElementsByTagName('TrainingStat')->length;
		}
		return $this->numberTrainingType;
	}

	/**
	 * Return HTTrainingType object
	 *
	 * @param Integer $type
	 * @return HTTrainingType
	 */
	public function getTrainingType($type)
	{
		$type = round($type);
		if($type >=0 && $type < $this->getNumberTrainingType())
		{
			if(!isset($this->trainingType[$type]) || $this->trainingType[$type] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//TrainingType[.="'.$type.'"]');
				$training = new DOMDocument('1.0', 'UTF-8');
				$training->appendChild($training->importNode($nodeList->item(0)->parentNode, true));
				$this->trainingType[$type] = new HTTrainingType($training);
			}
			return $this->trainingType[$type];
		}
		return null;
	}
}
class HTTrainingType extends HTXml
{
	private $type = null;
	private $numberTeams = null;
	private $percentTeams = null;

	/**
	 * @param DOMDocument $xml
	 */
	public function __construct($xml)
	{
		$this->xmlText = $xml->saveXML();
		$this->xml = $xml;
	}

	/**
	 * Return type of training
	 *
	 * @return Integer
	 */
	public function getType()
	{
		if(!isset($this->type) || $this->type === null)
		{
			$this->type = $this->getXml()->getElementsByTagName('TrainingType')->item(0)->nodeValue;
		}
		return $this->type;
	}

	/**
	 * Return number of teams training this type of training
	 *
	 * @return Integer
	 */
	public function getNumberOfTeams()
	{
		if(!isset($this->numberTeams) || $this->numberTeams === null)
		{
			$this->numberTeams = $this->getXml()->getElementsByTagName('NumberOfTeams')->item(0)->nodeValue;
		}
		return $this->numberTeams;
	}

	/**
	 * Return percentage of teams training this type of training
	 *
	 * @return Integer
	 */
	public function getPercentageOfTeams()
	{
		if(!isset($this->percentTeams) || $this->percentTeams === null)
		{
			$this->percentTeams = $this->getXml()->getElementsByTagName('FractionOfTeams')->item(0)->nodeValue;
		}
		return $this->percentTeams;
	}
}
class HTTeamTransferHistory extends HTCommonTeam
{
	private $activatedDate = null;
	private $sumBuys = null;
	private $sumSales = null;
	private $numberBuys = null;
	private $numberSales = null;
	private $numberTransfers = null;
	private $transfers = array();
	private $startDate = null;
	private $endDate = null;

	/**
	 * Return team activated date
	 *
	 * @param String $format (php date() function format)
	 * @return String
	 */
	public function getTeamActivatedDate($format = null)
	{
		if(!isset($this->activatedDate) || $this->activatedDate === null)
		{
			$this->activatedDate = $this->getXml()->getElementsByTagName('ActivatedDate')->item(0)->nodeValue;
			if($format !== null)
			{
				$this->activatedDate = HTFunction::convertDate($this->activatedDate, $format);
			}
		}
		return $this->activatedDate;
	}

	/**
	 * Return total sum of teams buys
	 *
	 * @return Integer
	 */
	public function getSumOfBuys()
	{
		if(!isset($this->sumBuys) || $this->sumBuys === null)
		{
			$this->sumBuys = $this->getXml()->getElementsByTagName('TotalSumOfBuys')->item(0)->nodeValue;
		}
		return $this->sumBuys;
	}

	/**
	 * Return total sum of teams sales
	 *
	 * @return Integer
	 */
	public function getSumOfSales()
	{
		if(!isset($this->sumSales) || $this->sumSales === null)
		{
			$this->sumSales = $this->getXml()->getElementsByTagName('TotalSumOfSales')->item(0)->nodeValue;
		}
		return $this->sumSales;
	}

	/**
	 * Return number of teams buys
	 *
	 * @return Integer
	 */
	public function getNumberOfBuys()
	{
		if(!isset($this->numberBuys) || $this->numberBuys === null)
		{
			$this->numberBuys = $this->getXml()->getElementsByTagName('NumberOfBuys')->item(0)->nodeValue;
		}
		return $this->numberBuys;
	}

	/**
	 * Return number of teams sales
	 *
	 * @return Integer
	 */
	public function getNumberOfSales()
	{
		if(!isset($this->numberSales) || $this->numberSales === null)
		{
			$this->numberSales = $this->getXml()->getElementsByTagName('NumberOfSales')->item(0)->nodeValue;
		}
		return $this->numberSales;
	}

	/**
	 * Return number of transfers listed by request
	 * Default period is past 7 days, if you specify a date
	 * period is one month before the date
	 *
	 * @return Integer
	 */
	public function getNumberTransfersDuringPeriod()
	{
		if(!isset($this->numberTransfers) || $this->numberTransfers === null)
		{
			$this->numberTransfers = $this->getXml()->getElementsByTagName('Transfer')->length;
		}
		return $this->numberTransfers;
	}

	/**
	 * Return HTTransfer object
	 *
	 * @param Integer $index (between 0 and value returned by getNumberTransfersDuringPeriod() method)
	 * @return HTTransfer
	 */
	public function getTransfer($index)
	{
		$index = round($index);
		if($index >=0 && $index < $this->getNumberTransfersDuringPeriod())
		{
			if(!isset($this->transfers[$index]) || $this->transfers[$index] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//Transfer');
				$transfer = new DOMDocument('1.0', 'UTF-8');
				$transfer->appendChild($transfer->importNode($nodeList->item($index), true));
				$this->transfers[$index] = new HTTransfer($transfer);
			}
			return $this->transfers[$index];
		}
		return null;
	}

	/**
	 * Return start date of transfers list
	 *
	 * @param String $format (php date() function format)
	 * @return String
	 */
	public function getStartDate($format = null)
	{
		if(!isset($this->startDate) || $this->startDate === null)
		{
			$this->startDate = $this->getXml()->getElementsByTagName('StartDate')->item(0)->nodeValue;
			if($format !== null)
			{
				$this->startDate = HTFunction::convertDate($this->startDate, $format);
			}
		}
		return $this->startDate;
	}

	/**
	 * Return end date of transfers list
	 *
	 * @param String $format (php date() function format)
	 * @return String
	 */
	public function getEndDate($format = null)
	{
		if(!isset($this->endDate) || $this->endDate === null)
		{
			$this->endDate = $this->getXml()->getElementsByTagName('EndDate')->item(0)->nodeValue;
			if($format !== null)
			{
				$this->endDate = HTFunction::convertDate($this->endDate, $format);
			}
		}
		return $this->endDate;
	}
}
class HTPlayerTransferHistory extends HTGlobal
{
	private $startDate = null;
	private $endDate = null;
	private $numberTransfers = null;
	private $playerId = null;
	private $playerName = null;
	private $transfers = null;

	/**
	 * Return start date of transfers list
	 *
	 * @param String $format (php date() function format)
	 * @return String
	 */
	public function getStartDate($format = null)
	{
		if(!isset($this->startDate) || $this->startDate === null)
		{
			$this->startDate = $this->getXml()->getElementsByTagName('StartDate')->item(0)->nodeValue;
			if($format !== null)
			{
				$this->startDate = HTFunction::convertDate($this->startDate, $format);
			}
		}
		return $this->startDate;
	}

	/**
	 * Return end date of transfers list
	 *
	 * @param String $format (php date() function format)
	 * @return String
	 */
	public function getEndDate($format = null)
	{
		if(!isset($this->endDate) || $this->endDate === null)
		{
			$this->endDate = $this->getXml()->getElementsByTagName('EndDate')->item(0)->nodeValue;
			if($format !== null)
			{
				$this->endDate = HTFunction::convertDate($this->endDate, $format);
			}
		}
		return $this->endDate;
	}

	/**
	 * Return player id
	 *
	 * @return Integer
	 */
	public function getPlayerId()
	{
		if(!isset($this->playerId) || $this->playerId === null)
		{
			$this->playerId = $this->getXml()->getElementsByTagName('PlayerID')->item(0)->nodeValue;
		}
		return $this->playerId;
	}

	/**
	 * Return player name
	 *
	 * @return String
	 */
	public function getPlayerName()
	{
		if(!isset($this->playerName) || $this->playerName === null)
		{
			$this->playerName = $this->getXml()->getElementsByTagName('PlayerName')->item(0)->nodeValue;
		}
		return $this->playerName;
	}

	/**
	 * Return transfers number
	 *
	 * @return Integer
	 */
	public function getNumberTransfers()
	{
		if(!isset($this->numberTransfers) || $this->numberTransfers === null)
		{
			$this->numberTransfers = $this->getXml()->getElementsByTagName('Transfer')->length;
		}
		return $this->numberTransfers;
	}

	/**
	 * Return HTTransfer object
	 *
	 * @param Integer $index (between 1 and value returned by getNumberTransfers() method)
	 * @return HTTransfer
	 */
	public function getTransfer($index)
	{
		$index = round($index);
		if($index > 0 && $index <= $this->getNumberTransfers())
		{
			--$index;
			if(!isset($this->transfers[$index]) || $this->transfers[$index] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//Transfer');
				$player = $xpath->query('//Player');
				$transfer = new DOMDocument('1.0', 'UTF-8');
				$transfer->appendChild($transfer->importNode($nodeList->item($index), true));
				$transfer->appendChild($transfer->importNode($player->item(0), true));
				$this->transfers[$index] = new HTTransfer($transfer);
			}
			return $this->transfers[$index];
		}
		return null;
	}
}
class HTTransfer extends HTXml
{
	private $transferId = null;
	private $deadline = null;
	private $transferType = null;
	private $playerId = null;
	private $playerName = null;
	private $buyerId = null;
	private $buyerName = null;
	private $sellerId = null;
	private $sellerName = null;
	private $price = null;
	private $tsi = null;
	const UNAVAILABLE = 'unavailable';

	/**
	 * @param DOMDocument $xml
	 */
	public function __construct($xml)
	{
		$this->xmlText = $xml->saveXML();
		$this->xml = $xml;
	}

	/**
	 * Return transfer id
	 *
	 * @return Integer
	 */
	public function getId()
	{
		if(!isset($this->transferId) || $this->transferId === null)
		{
			$this->transferId = $this->getXml()->getElementsByTagName('TransferID')->item(0)->nodeValue;
		}
		return $this->transferId;
	}

	/**
	 * Return deadline date
	 *
	 * @param String $format (php date() function format)
	 * @return String
	 */
	public function getDeadline($format = null)
	{
		if(!isset($this->deadline) || $this->deadline === null)
		{
			$this->deadline = $this->getXml()->getElementsByTagName('Deadline')->item(0)->nodeValue;
			if($format !== null)
			{
				$this->deadline = HTFunction::convertDate($this->deadline, $format);
			}
		}
		return $this->deadline;
	}

	/**
	 * Return transfer type : S for sale, B for buy
	 *
	 * @return String
	 */
	public function getType()
	{
		if(!isset($this->transferType) || $this->transferType === null)
		{
			$node = $this->getXml()->getElementsByTagName('TransferType');
			if($node !== null && $node->length)
			{
				$this->transferType = $node->item(0)->nodeValue;
			}
			else
			{
				$this->transferType = self::UNAVAILABLE ;
			}
		}
		if($this->transferType === self::UNAVAILABLE )
		{
			return null;
		}
		return $this->transferType;
	}

	/**
	 * Return player id
	 *
	 * @return Integer
	 */
	public function getPlayerId()
	{
		if(!isset($this->playerId) || $this->playerId === null)
		{
			$this->playerId = $this->getXml()->getElementsByTagName('PlayerID')->item(0)->nodeValue;
		}
		return $this->playerId;
	}

	/**
	 * Return player name
	 *
	 * @return String
	 */
	public function getPlayerName()
	{
		if(!isset($this->playerName) || $this->playerName === null)
		{
			$this->playerName = $this->getXml()->getElementsByTagName('PlayerName')->item(0)->nodeValue;
		}
		return $this->playerName;
	}

	/**
	 * Return buyer team id
	 *
	 * @return Integer
	 */
	public function getBuyerTeamId()
	{
		if(!isset($this->buyerId) || $this->buyerId === null)
		{
			$this->buyerId = $this->getXml()->getElementsByTagName('BuyerTeamID')->item(0)->nodeValue;
		}
		return $this->buyerId;
	}

	/**
	 * Return buyer team name
	 *
	 * @return String
	 */
	public function getBuyerTeamName()
	{
		if(!isset($this->buyerName) || $this->buyerName === null)
		{
			$this->buyerName = $this->getXml()->getElementsByTagName('BuyerTeamName')->item(0)->nodeValue;
		}
		return $this->buyerName;
	}

	/**
	 * Return seller team id
	 *
	 * @return Integer
	 */
	public function getSellerTeamId()
	{
		if(!isset($this->sellerId) || $this->sellerId === null)
		{
			$this->sellerId = $this->getXml()->getElementsByTagName('SellerTeamID')->item(0)->nodeValue;
		}
		return $this->sellerId;
	}

	/**
	 * Return seller team name
	 *
	 * @return String
	 */
	public function getSellerTeamName()
	{
		if(!isset($this->sellerName) || $this->sellerName === null)
		{
			$this->sellerName = $this->getXml()->getElementsByTagName('SellerTeamName')->item(0)->nodeValue;
		}
		return $this->sellerName;
	}

	/**
	 * Return transfer price
	 *
	 * @param Integer $countryCurrency (Constant taken from HTMoney class)
	 * @return Integer
	 */
	public function getPrice($countryCurrency = null)
	{
		if(!isset($this->price) || $this->price === null)
		{
			$this->price = HTMoney::convert($this->getXml()->getElementsByTagName('Price')->item(0)->nodeValue, $countryCurrency);
		}
		return $this->price;
	}

	/**
	 * Return player tsi at transfer date
	 *
	 * @return Integer
	 */
	public function getTsi()
	{
		if(!isset($this->tsi) || $this->tsi === null)
		{
			$this->tsi = $this->getXml()->getElementsByTagName('TSI')->item(0)->nodeValue;
		}
		return $this->tsi;
	}
}
class HTTeamPlayers extends HTCommonSubscriber
{
	private $numberPlayers = null;
	protected $players = null;
	private $isPlaying = null;

	/**
	 * Return number players of team
	 *
	 * @return Integer
	 */
	public function getNumberPlayers()
	{
		if(!isset($this->numberPlayers) || $this->numberPlayers === null)
		{
			$this->numberPlayers = $this->getXml()->getElementsByTagName('Player')->length;
		}
		return $this->numberPlayers;
	}

	/**
	 * Return HTTeamPlayer player object
	 *
	 * @param Integer $index
	 * @return HTTeamPlayer
	 */
	public function getPlayer($index)
	{
		$index = round($index);
		if($index > 0 && $index <= $this->getNumberPlayers())
		{
			--$index;
			if(!isset($this->players[$index]) || $this->players[$index] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//Player');
				$player = new DOMDocument('1.0', 'UTF-8');
				$player->appendChild($player->importNode($nodeList->item($index), true));
				$this->players[$index] = new HTTeamPlayer($player);
			}
			return $this->players[$index];
		}
		return null;
	}

	/**
	 * Is team playing a match?
	 *
	 * @return Boolean
	 */
	public function isPlayingMatch()
	{
		if(!isset($this->isPlaying) || $this->isPlaying === null)
		{
			$this->isPlaying = strtolower($this->getXml()->getElementsByTagName('IsPlayingMatch')->item(0)->nodeValue) == "true";
		}
		return $this->isPlaying;
	}
}
class HTTeamOldCoaches extends HTTeamPlayers
{
	/**
	 * Return HTTeamOldCoach player object
	 *
	 * @param Integer $index
	 * @return HTTeamOldCoach
	 */
	public function getPlayer($index)
	{
		$index = round($index);
		if($index > 0 && $index <= $this->getNumberPlayers())
		{
			--$index;
			if(!isset($this->players[$index]) || $this->players[$index] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//Player');
				$player = new DOMDocument('1.0', 'UTF-8');
				$player->appendChild($player->importNode($nodeList->item($index), true));
				$this->players[$index] = new HTTeamOldCoach($player);
			}
			return $this->players[$index];
		}
		return null;
	}
}
class HTTeamOldPlayers extends HTTeamPlayers
{
	/**
	 * Return HTTeamOldPlayer player object
	 *
	 * @param Integer $index
	 * @return HTTeamOldPlayer
	 */
	public function getPlayer($index)
	{
		$index = round($index);
		if($index > 0 && $index <= $this->getNumberPlayers())
		{
			--$index;
			if(!isset($this->players[$index]) || $this->players[$index] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//Player');
				$player = new DOMDocument('1.0', 'UTF-8');
				$player->appendChild($player->importNode($nodeList->item($index), true));
				$this->players[$index] = new HTTeamOldPlayer($player);
			}
			return $this->players[$index];
		}
		return null;
	}
}
class HTTeamPlayer extends HTXml
{
	private $playerId = null;
	private $playerFirstName = null;
	private $playerLastName = null;
	private $playerNickName = null;
	private $playerNumber = null;
	private $age = null;
	private $days = null;
	private $tsi = null;
	private $form = null;
	private $statement = null;
	private $experience = null;
	private $leadership = null;
	private $salary = null;
	private $isAbroad = null;
	private $agreeability = null;
	private $aggressiveness = null;
	private $honesty = null;
	private $leagueGoals = null;
	private $cupGoals = null;
	private $friendlyGoals = null;
	private $carrerGoals = null;
	private $carrerHattricks = null;
	private $specialityCode = null;
	private $transferListed = null;
	private $nationalTeamId = null;
	private $countryId = null;
	private $caps = null;
	private $capsU20 = null;
	private $cards = null;
	private $injuryLevel = null;
	private $trainerType = null;
	private $trainerSkill = null;
	private $isTrainer = null;
	private $birthday = null;
	const UNAVAILABLE = 'NOT AVAILABLE';
	const DAYSINYEAR = 112;

	/**
	 * @param DOMDocument $xml
	 */
	public function __construct($xml)
	{
		$this->xmlText = $xml->saveXML();
		$this->xml = $xml;
	}

	/**
	 * Return player id
	 *
	 * @return Integer
	 */
	public function getId()
	{
		if(!isset($this->playerId) || $this->playerId === null)
		{
			$this->playerId = $this->getXml()->getElementsByTagName('PlayerID')->item(0)->nodeValue;
		}
		return $this->playerId;
	}

	/**
	 * Return player first name
	 *
	 * @return String
	 */
	public function getFirstName()
	{
		if(!isset($this->playerFirstName) || $this->playerFirstName === null)
		{
			$this->playerFirstName = $this->getXml()->getElementsByTagName('FirstName')->item(0)->nodeValue;
		}
		return $this->playerFirstName;
	}

	/**
	 * Return player last name
	 *
	 * @return String
	 */
	public function getLastName()
	{
		if(!isset($this->playerLastName) || $this->playerLastName === null)
		{
			$this->playerLastName = $this->getXml()->getElementsByTagName('LastName')->item(0)->nodeValue;
		}
		return $this->playerLastName;
	}

	/**
	 * Return player nickname
	 *
	 * @return String
	 */
	public function getNickName()
	{
		if(!isset($this->playerNickName) || $this->playerNickName === null)
		{
			$this->playerNickName = $this->getXml()->getElementsByTagName('NickName')->item(0)->nodeValue;
		}
		return $this->playerNickName;
	}

	/**
	 * Return player full name
	 *
	 * @return String
	 */
	public function getName()
	{
		$name = $this->getFirstName().' ';
		if($this->getNickName() !== null && $this->getNickName() !== '')
		{
			$name .= $this->getNickName().' ';
		}
		$name .= $this->getLastName();
		return $name;
	}

	/**
	 * Return player shirt number if team is hattrick supporter
	 *
	 * @return Integer
	 */
	public function getShirtNumber()
	{
		if(!isset($this->playerNumber) || $this->playerNumber === null)
		{
			$this->playerNumber = $this->getXml()->getElementsByTagName('PlayerNumber')->item(0)->nodeValue;
		}
		if($this->playerNumber == 100)
		{
			return null;
		}
		return $this->playerNumber;
	}

	/**
	 * Return player age
	 *
	 * @return Integer
	 */
	public function getAge()
	{
		if(!isset($this->age) || $this->age === null)
		{
			$this->age = $this->getXml()->getElementsByTagName('Age')->item(0)->nodeValue;
		}
		return $this->age;
	}

	/**
	 * Return player age days
	 *
	 * @return Integer
	 */
	public function getDays()
	{
		if(!isset($this->days) || $this->days === null)
		{
			$this->days = $this->getXml()->getElementsByTagName('AgeDays')->item(0)->nodeValue;
		}
		return $this->days;
	}

	/**
	 * Return player TSI
	 *
	 * @return Integer
	 */
	public function getTsi()
	{
		if(!isset($this->tsi) || $this->tsi === null)
		{
			$this->tsi = $this->getXml()->getElementsByTagName('TSI')->item(0)->nodeValue;
		}
		return $this->tsi;
	}

	/**
	 * Return player form level
	 *
	 * @return Integer
	 */
	public function getForm()
	{
		if(!isset($this->form) || $this->form === null)
		{
			$this->form = $this->getXml()->getElementsByTagName('PlayerForm')->item(0)->nodeValue;
		}
		return $this->form;
	}

	/**
	 * Return player statement
	 *
	 * @return Unknow
	 */
	public function getStatement()
	{
		if(!isset($this->statement) || $this->statement === null)
		{
			$this->statement = $this->getXml()->getElementsByTagName('Statement')->item(0)->nodeValue;
		}
		return $this->statement;
	}

	/**
	 * Return player experience level
	 *
	 * @return Integer
	 */
	public function getExperience()
	{
		if(!isset($this->experience) || $this->experience === null)
		{
			$this->experience = $this->getXml()->getElementsByTagName('Experience')->item(0)->nodeValue;
		}
		return $this->experience;
	}

	/**
	 * Return player leadership level
	 *
	 * @return Integer
	 */
	public function getLeadership()
	{
		if(!isset($this->leadership) || $this->leadership === null)
		{
			$this->leadership = $this->getXml()->getElementsByTagName('Leadership')->item(0)->nodeValue;
		}
		return $this->leadership;
	}

	/**
	 * Return player salary, in currency if specify
	 *
	 * @param Integer $countryCurrency (Constant taken from HTMoney class)
	 * @return Integer
	 */
	public function getSalary($countryCurrency = null)
	{
		if(!isset($this->salary) || $this->salary === null)
		{
			$this->salary = HTMoney::convert($this->getXml()->getElementsByTagName('Salary')->item(0)->nodeValue, $countryCurrency);
		}
		return $this->salary;
	}

	/**
	 * Is player abroad ?
	 *
	 * @return Boolean
	 */
	public function isAbroad()
	{
		if(!isset($this->isAbroad) || $this->isAbroad === null)
		{
			$this->isAbroad = $this->getXml()->getElementsByTagName('IsAbroad')->item(0)->nodeValue == "1";
		}
		return $this->isAbroad;
	}

	/**
	 * Return player agreeability level
	 *
	 * @return Integer
	 */
	public function getAgreeability()
	{
		if(!isset($this->agreeability) || $this->agreeability === null)
		{
			$this->agreeability = $this->getXml()->getElementsByTagName('Agreeability')->item(0)->nodeValue;
		}
		return $this->agreeability;
	}

	/**
	 * Return player aggressiveness level
	 *
	 * @return Integer
	 */
	public function getAggressiveness()
	{
		if(!isset($this->aggressiveness) || $this->aggressiveness === null)
		{
			$this->aggressiveness = $this->getXml()->getElementsByTagName('Aggressiveness')->item(0)->nodeValue;
		}
		return $this->aggressiveness;
	}

	/**
	 * Return player honesty level
	 *
	 * @return Integer
	 */
	public function getHonesty()
	{
		if(!isset($this->honesty) || $this->honesty === null)
		{
			$this->honesty = $this->getXml()->getElementsByTagName('Honesty')->item(0)->nodeValue;
		}
		return $this->honesty;
	}

	/**
	 * Return player number goals in league
	 *
	 * @return Integer
	 */
	public function getGoalsInLeague()
	{
		if(!isset($this->leagueGoals) || $this->leagueGoals === null)
		{
			$this->leagueGoals = $this->getXml()->getElementsByTagName('LeagueGoals')->item(0)->nodeValue;
		}
		if($this->leagueGoals === self::UNAVAILABLE )
		{
			return null;
		}
		return $this->leagueGoals;
	}

	/**
	 * Return player number goals in cup
	 *
	 * @return Integer
	 */
	public function getGoalsInCup()
	{
		if(!isset($this->cupGoals) || $this->cupGoals === null)
		{
			$this->cupGoals = $this->getXml()->getElementsByTagName('CupGoals')->item(0)->nodeValue;
		}
		if($this->cupGoals === self::UNAVAILABLE )
		{
			return null;
		}
		return $this->cupGoals;
	}

	/**
	 * Return player number goals in friendly
	 *
	 * @return Integer
	 */
	public function getGoalsInFriendly()
	{
		if(!isset($this->friendlyGoals) || $this->friendlyGoals === null)
		{
			$this->friendlyGoals = $this->getXml()->getElementsByTagName('FriendliesGoals')->item(0)->nodeValue;
		}
		if($this->friendlyGoals === self::UNAVAILABLE )
		{
			return null;
		}
		return $this->friendlyGoals;
	}

	/**
	 * Return player number goals in his career
	 *
	 * @return Integer
	 */
	public function getGoalsInCareer()
	{
		if(!isset($this->carrerGoals) || $this->carrerGoals === null)
		{
			$this->carrerGoals = $this->getXml()->getElementsByTagName('CareerGoals')->item(0)->nodeValue;
		}
		if($this->carrerGoals === self::UNAVAILABLE )
		{
			return null;
		}
		return $this->carrerGoals;
	}

	/**
	 * Return player number hattricks in his career
	 *
	 * @return Integer
	 */
	public function getHattricksInCareer()
	{
		if(!isset($this->carrerHattricks) || $this->carrerHattricks === null)
		{
			$this->carrerHattricks = $this->getXml()->getElementsByTagName('CareerHattricks')->item(0)->nodeValue;
		}
		if($this->carrerHattricks === self::UNAVAILABLE )
		{
			return null;
		}
		return $this->carrerHattricks;
	}

	/**
	 * Return player speciality code
	 *
	 * @return Integer
	 */
	public function getSpeciality()
	{
		if(!isset($this->specialityCode) || $this->specialityCode === null)
		{
			$this->specialityCode = $this->getXml()->getElementsByTagName('Specialty')->item(0)->nodeValue;
		}
		return $this->specialityCode;
	}

	/**
	 * Is player transfer listed ?
	 *
	 * @return Boolean
	 */
	public function isTransferListed()
	{
		if(!isset($this->transferListed) || $this->transferListed === null)
		{
			$this->transferListed = $this->getXml()->getElementsByTagName('TransferListed')->item(0)->nodeValue == "1";
		}
		return $this->transferListed;
	}

	/**
	 * Return national team id if player selected
	 *
	 * @return Integer
	 */
	public function getNationalTeamId()
	{
		if(!isset($this->nationalTeamId) || $this->nationalTeamId === null)
		{
			$this->nationalTeamId = $this->getXml()->getElementsByTagName('NationalTeamID')->item(0)->nodeValue;
		}
		if($this->nationalTeamId == 0)
		{
			return null;
		}
		return $this->nationalTeamId;
	}

	/**
	 * Return player country id
	 *
	 * @return Integer
	 */
	public function getCountryId()
	{
		if(!isset($this->countryId) || $this->countryId === null)
		{
			$this->countryId = $this->getXml()->getElementsByTagName('CountryID')->item(0)->nodeValue;
		}
		return $this->countryId;
	}

	/**
	 * Return player number caps in A country team
	 *
	 * @return Integer
	 */
	public function getACaps()
	{
		if(!isset($this->caps) || $this->caps === null)
		{
			$this->caps = $this->getXml()->getElementsByTagName('Caps')->item(0)->nodeValue;
		}
		return $this->caps;
	}

	/**
	 * Return player number caps in U20 country team
	 *
	 * @return Integer
	 */
	public function getU20Caps()
	{
		if(!isset($this->capsU20) || $this->capsU20 === null)
		{
			$this->capsU20 = $this->getXml()->getElementsByTagName('CapsU20')->item(0)->nodeValue;
		}
		return $this->capsU20;
	}

	/**
	 * Return player cards number
	 *
	 * @return Integer
	 */
	public function getCards()
	{
		if(!isset($this->cards) || $this->cards === null)
		{
			$this->cards = $this->getXml()->getElementsByTagName('Cards')->item(0)->nodeValue;
		}
		if($this->cards === self::UNAVAILABLE )
		{
			return null;
		}
		return $this->cards;
	}

	/**
	 * Return player injury level, -1 if not injured
	 *
	 * @return Integer
	 */
	public function getInjury()
	{
		if(!isset($this->injuryLevel) || $this->injuryLevel === null)
		{
			$this->injuryLevel = $this->getXml()->getElementsByTagName('InjuryLevel')->item(0)->nodeValue;
		}
		if($this->injuryLevel === self::UNAVAILABLE )
		{
			return null;
		}
		return $this->injuryLevel;
	}

	/**
	 * Is player a trainer ?
	 *
	 * @return Boolean
	 */
	public function isTrainer()
	{
		if(!isset($this->isTrainer) || $this->isTrainer === null)
		{
			$this->isTrainer = $this->getXml()->getElementsByTagName('TrainerData')->item(0)->hasChildNodes();
		}
		return $this->isTrainer;
	}

	/**
	 * Return player trainer type
	 *
	 * @return Integer
	 */
	public function getTrainerType()
	{
		if($this->isTrainer())
		{
			if(!isset($this->trainerType) || $this->trainerType === null)
			{
				$this->trainerType = $this->getXml()->getElementsByTagName('TrainerType')->item(0)->nodeValue;
			}
			return $this->trainerType;
		}
		return null;
	}

	/**
	 * Return player trainer skill
	 *
	 * @return Integer
	 */
	public function getTrainerSkill()
	{
		if($this->isTrainer())
		{
			if(!isset($this->trainerSkill) || $this->trainerSkill === null)
			{
				$this->trainerSkill = $this->getXml()->getElementsByTagName('TrainerSkill')->item(0)->nodeValue;
			}
			return $this->trainerSkill;
		}
		return null;
	}

	/**
	 * Return player next birthday
	 *
	 * @param String $format (php date() function format)
	 * @return String
	 */
	public function getNextBirthDay($format = null)
	{
		if(!isset($this->birthday) || $this->birthday === null)
		{
			$this->birthday = date('Y-m-d', time()+((self::DAYSINYEAR-$this->getDays())*24*3600));
			if($format !== null)
			{
				$this->birthday = HTFunction::convertDate($this->birthday, $format);
			}
		}
		return $this->birthday;
	}
}
class HTTeamOldCoach extends HTTeamPlayer
{
	private $trainerType = null;
	private $trainerSkill = null;

	/**
	 * Return trainer type :  0 = Defensive ;  1 = Offensive ;  2 = Balanced
	 *
	 * @return Integer
	 */
	public function getTrainerType()
	{
		if(!isset($this->trainerType) || $this->trainerType === null)
		{
			$this->trainerType = $this->getXml()->getElementsByTagName('TrainerType')->item(0)->nodeValue;
		}
		return $this->trainerType;
	}

	/**
	 * Return trainer skill value
	 *
	 * @return Integer
	 */
	public function getTrainerSkill()
	{
		if(!isset($this->trainerSkill) || $this->trainerSkill === null)
		{
			$this->trainerSkill = $this->getXml()->getElementsByTagName('TrainerSkill')->item(0)->nodeValue;
		}
		return $this->trainerSkill;
	}
}
class HTTeamOldPlayer extends HTTeamPlayer
{
	private $teamId = null;
	private $teamName = null;
	private $leagueName = null;

	/**
	 * Return current team id of old player
	 *
	 * @return Integer
	 */
	public function getTeamId()
	{
		if(!isset($this->teamId) || $this->teamId === null)
		{
			$xpath = new DOMXPath($this->getXml());
			$nodeList = $xpath->query('//OwningTeam/TeamID');
			$this->teamId = $nodeList->item(0)->nodeValue;
		}
		return $this->teamId;
	}

	/**
	 * Return current team name of old player
	 *
	 * @return String
	 */
	public function getTeamName()
	{
		if(!isset($this->teamName) || $this->teamName === null)
		{
			$xpath = new DOMXPath($this->getXml());
			$nodeList = $xpath->query('//OwningTeam/TeamName');
			$this->teamName = $nodeList->item(0)->nodeValue;
		}
		return $this->teamName;
	}

	/**
	 * Return current league name of old player
	 *
	 * @return String
	 */
	public function getLeagueName()
	{
		if(!isset($this->leagueName) || $this->leagueName === null)
		{
			$xpath = new DOMXPath($this->getXml());
			$nodeList = $xpath->query('//OwningTeam/LeagueName');
			$this->leagueName = $nodeList->item(0)->nodeValue;
		}
		return $this->leagueName;
	}
}
class HTPlayer extends HTCommonSubscriber
{
	private $playerId = null;
	private $playerFirstName = null;
	private $playerLastName = null;
	private $playerNickName = null;
	private $playerNumberAvailable = null;
	private $playerNumber = null;
	private $age = null;
	private $days = null;
	private $form = null;
	private $cards = null;
	private $injuryLevel = null;
	private $statementAvailable = null;
	private $statement = null;
	private $playerLanguageAvailable = null;
	private $playerLanguage = null;
	private $playerLanguageIdAvailable = null;
	private $playerLanguageId = null;
	private $agreeability = null;
	private $aggressiveness = null;
	private $honesty = null;
	private $experience = null;
	private $leadership = null;
	private $specialityCode = null;
	private $nativeLeagueId = null;
	private $nativeLeagueName = null;
	private $tsi = null;
	private $leagueId = null;
	private $salary = null;
	private $isAbroad = null;
	private $caps = null;
	private $capsU20 = null;
	private $leagueGoals = null;
	private $cupGoals = null;
	private $carrerGoals = null;
	private $carrerHattricks = null;
	private $transferListed = null;
	private $skillsAvailable = null;
	private $stamina = null;
	private $keeper = null;
	private $playmaker = null;
	private $scorer = null;
	private $passing = null;
	private $winger = null;
	private $defender = null;
	private $setPieces = null;
	private $trainerType = null;
	private $trainerSkill = null;
	private $isTrainer = null;
	private $birthday = null;

	/**
	 * Return player id
	 *
	 * @return Integer
	 */
	public function getId()
	{
		if(!isset($this->playerId) || $this->playerId === null)
		{
			$this->playerId = $this->getXml()->getElementsByTagName('PlayerID')->item(0)->nodeValue;
		}
		return $this->playerId;
	}

	/**
	 * Return player first name
	 *
	 * @return String
	 */
	public function getFirstName()
	{
		if(!isset($this->playerFirstName) || $this->playerFirstName === null)
		{
			$this->playerFirstName = $this->getXml()->getElementsByTagName('FirstName')->item(0)->nodeValue;
		}
		return $this->playerFirstName;
	}

	/**
	 * Return player last name
	 *
	 * @return String
	 */
	public function getLastName()
	{
		if(!isset($this->playerLastName) || $this->playerLastName === null)
		{
			$this->playerLastName = $this->getXml()->getElementsByTagName('LastName')->item(0)->nodeValue;
		}
		return $this->playerLastName;
	}

	/**
	 * Return player nickname
	 *
	 * @return String
	 */
	public function getNickName()
	{
		if(!isset($this->playerNickName) || $this->playerNickName === null)
		{
			$this->playerNickName = $this->getXml()->getElementsByTagName('NickName')->item(0)->nodeValue;
		}
		return $this->playerNickName;
	}

	/**
	 * Return player full name
	 *
	 * @return String
	 */
	public function getName()
	{
		$name = $this->getFirstName().' ';
		if($this->getNickName() !== null && $this->getNickName() !== '')
		{
			$name .= $this->getNickName().' ';
		}
		$name .= $this->getLastName();
		return $name;
	}

	/**
	 * Return player shirt number if team is hattrick supporter
	 *
	 * @return Integer
	 */
	public function getShirtNumber()
	{
		if(!isset($this->playerNumberAvailable) || $this->playerNumberAvailable === null)
		{
			$this->playerNumberAvailable = $this->getXml()->getElementsByTagName('PlayerNumber')->item(0)->nodeValue;
		}
		if($this->playerNumberAvailable < 100)
		{
			if(!isset($this->playerNumber) || $this->playerNumber === null)
			{
				$this->playerNumber = $this->getXml()->getElementsByTagName('PlayerNumber')->item(0)->nodeValue;
			}
			return $this->playerNumber;
		}
		return null;
	}

	/**
	 * Return player age
	 *
	 * @return Integer
	 */
	public function getAge()
	{
		if(!isset($this->age) || $this->age === null)
		{
			$this->age = $this->getXml()->getElementsByTagName('Age')->item(0)->nodeValue;
		}
		return $this->age;
	}

	/**
	 * Return player age days
	 *
	 * @return Integer
	 */
	public function getDays()
	{
		if(!isset($this->days) || $this->days === null)
		{
			$this->days = $this->getXml()->getElementsByTagName('AgeDays')->item(0)->nodeValue;
		}
		return $this->days;
	}

	/**
	 * Return player TSI
	 *
	 * @return Integer
	 */
	public function getTsi()
	{
		if(!isset($this->tsi) || $this->tsi === null)
		{
			$this->tsi = $this->getXml()->getElementsByTagName('TSI')->item(0)->nodeValue;
		}
		return $this->tsi;
	}

	/**
	 * Return player league id
	 *
	 * @return Integer
	 */
	public function getLeagueId()
	{
		if(!isset($this->leagueId) || $this->leagueId === null)
		{
			$this->leagueId = $this->getXml()->getElementsByTagName('LeagueID')->item(0)->nodeValue;
		}
		return $this->leagueId;
	}

	/**
	 * Return player form level
	 *
	 * @return Integer
	 */
	public function getForm()
	{
		if(!isset($this->form) || $this->form === null)
		{
			$this->form = $this->getXml()->getElementsByTagName('PlayerForm')->item(0)->nodeValue;
		}
		return $this->form;
	}

	/**
	 * Return player cards number
	 *
	 * @return Integer
	 */
	public function getCards()
	{
		if(!isset($this->cards) || $this->cards === null)
		{
			$this->cards = $this->getXml()->getElementsByTagName('Cards')->item(0)->nodeValue;
		}
		return $this->cards;
	}

	/**
	 * Return player injury level, -1 if not injured
	 *
	 * @return Integer
	 */
	public function getInjury()
	{
		if(!isset($this->injuryLevel) || $this->injuryLevel === null)
		{
			$this->injuryLevel = $this->getXml()->getElementsByTagName('InjuryLevel')->item(0)->nodeValue;
		}
		return $this->injuryLevel;
	}

	/**
	 * Return player statement
	 *
	 * @return Integer
	 */
	public function getStatement()
	{
		if(!isset($this->statementAvailable) || $this->statementAvailable === null)
		{
			$this->statementAvailable = $this->getXml()->getElementsByTagName('Statement')->length;
		}
		if($this->statementAvailable)
		{
			if(!isset($this->statement) || $this->statement === null)
			{
				$this->statement = $this->getXml()->getElementsByTagName('Statement')->item(0)->nodeValue;
			}
			return $this->statement;
		}
		return null;
	}

	/**
	 * Return player language
	 *
	 * @return Integer
	 */
	public function getLanguage()
	{
		if(!isset($this->playerLanguageAvailable) || $this->playerLanguageAvailable === null)
		{
			$this->playerLanguageAvailable = $this->getXml()->getElementsByTagName('PlayerLanguage')->length;
		}
		if($this->playerLanguageAvailable)
		{
			if(!isset($this->playerLanguage) || $this->playerLanguage === null)
			{
				$this->playerLanguage = $this->getXml()->getElementsByTagName('PlayerLanguage')->item(0)->nodeValue;
			}
			return $this->playerLanguage;
		}
		return null;
	}

	/**
	 * Return player language id
	 *
	 * @return Integer
	 */
	public function getLanguageId()
	{
		if(!isset($this->playerLanguageIdAvailable) || $this->playerLanguageIdAvailable === null)
		{
			$this->playerLanguageIdAvailable = $this->getXml()->getElementsByTagName('PlayerLanguageID')->length;
		}
		if($this->playerLanguageIdAvailable)
		{
			if(!isset($this->playerLanguageId) || $this->playerLanguageId === null)
			{
				$this->playerLanguageId = $this->getXml()->getElementsByTagName('PlayerLanguageID')->item(0)->nodeValue;
			}
			return $this->playerLanguageId;
		}
		return null;
	}

	/**
	 * Return player experience level
	 *
	 * @return Integer
	 */
	public function getExperience()
	{
		if(!isset($this->experience) || $this->experience === null)
		{
			$this->experience = $this->getXml()->getElementsByTagName('Experience')->item(0)->nodeValue;
		}
		return $this->experience;
	}

	/**
	 * Return player leadership level
	 *
	 * @return Integer
	 */
	public function getLeadership()
	{
		if(!isset($this->leadership) || $this->leadership === null)
		{
			$this->leadership = $this->getXml()->getElementsByTagName('Leadership')->item(0)->nodeValue;
		}
		return $this->leadership;
	}

	/**
	 * Return player salary, in currency if specify
	 *
	 * @param Integer $countryCurrency (Constant taken from HTMoney class)
	 * @return Integer
	 */
	public function getSalary($countryCurrency = null)
	{
		if(!isset($this->salary) || $this->salary === null)
		{
			$this->salary = HTMoney::convert($this->getXml()->getElementsByTagName('Salary')->item(0)->nodeValue, $countryCurrency);
		}
		return $this->salary;
	}

	/**
	 * Is player abroad ?
	 *
	 * @return Boolean
	 */
	public function isAbroad()
	{
		if(!isset($this->isAbroad) || $this->isAbroad === null)
		{
			$this->isAbroad = strtolower($this->getXml()->getElementsByTagName('IsAbroad')->item(0)->nodeValue) == "true";
		}
		return $this->isAbroad;
	}

	/**
	 * Return player speciality code
	 *
	 * @return Integer
	 */
	public function getSpeciality()
	{
		if(!isset($this->specialityCode) || $this->specialityCode === null)
		{
			$this->specialityCode = $this->getXml()->getElementsByTagName('Specialty')->item(0)->nodeValue;
		}
		return $this->specialityCode;
	}

	/**
	 * Return player native league id
	 *
	 * @return Integer
	 */
	public function getNativeLeagueId()
	{
		if(!isset($this->nativeLeagueId) || $this->nativeLeagueId === null)
		{
			$this->nativeLeagueId = $this->getXml()->getElementsByTagName('NativeLeagueID')->item(0)->nodeValue;
		}
		return $this->nativeLeagueId;
	}

	/**
	 * Return player native league name
	 *
	 * @return Integer
	 */
	public function getNativeLeagueName()
	{
		if(!isset($this->nativeLeagueName) || $this->nativeLeagueName === null)
		{
			$this->nativeLeagueName = $this->getXml()->getElementsByTagName('NativeLeagueName')->item(0)->nodeValue;
		}
		return $this->nativeLeagueName;
	}

	/**
	 * Is player transfer listed ?
	 *
	 * @return Boolean
	 */
	public function isTransferListed()
	{
		if(!isset($this->transferListed) || $this->transferListed === null)
		{
			$this->transferListed = strtolower($this->getXml()->getElementsByTagName('TransferListed')->item(0)->nodeValue) == "true";
		}
		return $this->transferListed;
	}

	/**
	 * Return player number caps in A country team
	 *
	 * @return Integer
	 */
	public function getACaps()
	{
		if(!isset($this->caps) || $this->caps === null)
		{
			$this->caps = $this->getXml()->getElementsByTagName('Caps')->item(0)->nodeValue;
		}
		return $this->caps;
	}

	/**
	 * Return player number caps in U20 country team
	 *
	 * @return Integer
	 */
	public function getU20Caps()
	{
		if(!isset($this->capsU20) || $this->capsU20 === null)
		{
			$this->capsU20 = $this->getXml()->getElementsByTagName('CapsU20')->item(0)->nodeValue;
		}
		return $this->capsU20;
	}

	/**
	 * Does player skills available ?
	 *
	 * @return Boolean
	 */
	public function isSkillsAvailable()
	{
		if(!isset($this->skillsAvailable) || $this->skillsAvailable === null)
		{
			$this->skillsAvailable = $this->getXml()->getElementsByTagName('PlayerSkills')->item(0)->childNodes->length !== 0;
		}
		return $this->skillsAvailable;
	}

	/**
	 * Return player stamina level
	 *
	 * @return Integer
	 */
	public function getStamina()
	{
		if($this->isSkillsAvailable())
		{
			if(!isset($this->stamina) || $this->stamina === null)
			{
				$this->stamina = $this->getXml()->getElementsByTagName('StaminaSkill')->item(0)->nodeValue;
			}
			return $this->stamina;
		}
		return null;
	}

	/**
	 * Return player keeper level
	 *
	 * @return Integer
	 */
	public function getKeeper()
	{
		if($this->isSkillsAvailable())
		{
			if(!isset($this->keeper) || $this->keeper === null)
			{
				$this->keeper = $this->getXml()->getElementsByTagName('KeeperSkill')->item(0)->nodeValue;
			}
			return $this->keeper;
		}
		return null;
	}

	/**
	 * Return player playmaker level
	 *
	 * @return Integer
	 */
	public function getPlaymaker()
	{
		if($this->isSkillsAvailable())
		{
			if(!isset($this->playmaker) || $this->playmaker === null)
			{
				$this->playmaker = $this->getXml()->getElementsByTagName('PlaymakerSkill')->item(0)->nodeValue;
			}
			return $this->playmaker;
		}
		return null;
	}

	/**
	 * Return player scorer level
	 *
	 * @return Integer
	 */
	public function getScorer()
	{
		if($this->isSkillsAvailable())
		{
			if(!isset($this->scorer) || $this->scorer === null)
			{
				$this->scorer = $this->getXml()->getElementsByTagName('ScorerSkill')->item(0)->nodeValue;
			}
			return $this->scorer;
		}
		return null;
	}

	/**
	 * Return player passing level
	 *
	 * @return Integer
	 */
	public function getPassing()
	{
		if($this->isSkillsAvailable())
		{
			if(!isset($this->passing) || $this->passing === null)
			{
				$this->passing = $this->getXml()->getElementsByTagName('PassingSkill')->item(0)->nodeValue;
			}
			return $this->passing;
		}
		return null;
	}

	/**
	 * Return player winger level
	 *
	 * @return Integer
	 */
	public function getWinger()
	{
		if($this->isSkillsAvailable())
		{
			if(!isset($this->winger) || $this->winger === null)
			{
				$this->winger = $this->getXml()->getElementsByTagName('WingerSkill')->item(0)->nodeValue;
			}
			return $this->winger;
		}
		return null;
	}

	/**
	 * Return player defender level
	 *
	 * @return Integer
	 */
	public function getDefender()
	{
		if($this->isSkillsAvailable())
		{
			if(!isset($this->defender) || $this->defender === null)
			{
				$this->defender = $this->getXml()->getElementsByTagName('DefenderSkill')->item(0)->nodeValue;
			}
			return $this->defender;
		}
		return null;
	}

	/**
	 * Return player set pieces level
	 *
	 * @return Integer
	 */
	public function getSetPieces()
	{
		if($this->isSkillsAvailable())
		{
			if(!isset($this->setPieces) || $this->setPieces === null)
			{
				$this->setPieces = $this->getXml()->getElementsByTagName('SetPiecesSkill')->item(0)->nodeValue;
			}
			return $this->setPieces;
		}
		return null;
	}

	/**
	 * Return player agreeability level
	 *
	 * @return Integer
	 */
	public function getAgreeability()
	{
		if(!isset($this->agreeability) || $this->agreeability === null)
		{
			$this->agreeability = $this->getXml()->getElementsByTagName('Agreeability')->item(0)->nodeValue;
		}
		return $this->agreeability;
	}

	/**
	 * Return player aggressiveness level
	 *
	 * @return Integer
	 */
	public function getAggressiveness()
	{
		if(!isset($this->aggressiveness) || $this->aggressiveness === null)
		{
			$this->aggressiveness = $this->getXml()->getElementsByTagName('Aggressiveness')->item(0)->nodeValue;
		}
		return $this->aggressiveness;
	}

	/**
	 * Return player honesty level
	 *
	 * @return Integer
	 */
	public function getHonesty()
	{
		if(!isset($this->honesty) || $this->honesty === null)
		{
			$this->honesty = $this->getXml()->getElementsByTagName('Honesty')->item(0)->nodeValue;
		}
		return $this->honesty;
	}

	/**
	 * Is player a trainer ?
	 *
	 * @return Boolean
	 */
	public function isTrainer()
	{
		if(!isset($this->isTrainer) || $this->isTrainer === null)
		{
			$this->isTrainer = $this->getXml()->getElementsByTagName('TrainerData')->item(0)->hasChildNodes();
		}
		return $this->isTrainer;
	}

	/**
	 * Return player trainer type
	 *
	 * @return Integer
	 */
	public function getTrainerType()
	{
		if($this->isTrainer())
		{
			if(!isset($this->trainerType) || $this->trainerType === null)
			{
				$this->trainerType = $this->getXml()->getElementsByTagName('TrainerType')->item(0)->nodeValue;
			}
			return $this->trainerType;
		}
		return null;
	}

	/**
	 * Return player trainer skill
	 *
	 * @return Integer
	 */
	public function getTrainerSkill()
	{
		if($this->isTrainer())
		{
			if(!isset($this->trainerSkill) || $this->trainerSkill === null)
			{
				$this->trainerSkill = $this->getXml()->getElementsByTagName('TrainerSkill')->item(0)->nodeValue;
			}
			return $this->trainerSkill;
		}
		return null;
	}

	/**
	 * Return player next birthday
	 *
	 * @param String $format (php date() function format)
	 * @return String
	 */
	public function getNextBirthDay($format = null)
	{
		if(!isset($this->birthday) || $this->birthday === null)
		{
			$this->birthday = $this->getXml()->getElementsByTagName('NextBirthDay')->item(0)->nodeValue;
			if($format !== null)
			{
				$this->birthday = HTFunction::convertDate($this->birthday, $format);
			}
		}
		return $this->birthday;
	}
}
class HTTeamMatches extends HTCommonTeam
{
	private $numberMatches = null;
	private $matches = null;
	private $nextMatch = null;
	private $nextMatches = null;
	private $lastMatch = null;
	private $lastMatches = null;
	const NOT_PLAYED = 'UPCOMING';
	const PLAYED = 'FINISHED';
	const PLAYING = 'ONGOING';

	/**
	 * Return number of matches in list
	 *
	 * @return Integer
	 */
	public function getNumberMatches()
	{
		if(!isset($this->numberMatches) || $this->numberMatches === null)
		{
			$this->numberMatches = $this->getXml()->getElementsByTagName('Match')->length;
		}
		return $this->numberMatches;
	}

	/**
	 * Return HTTeamMatch object
	 *
	 * @param Integer $index
	 * @return HTTeamMatch
	 */
	public function getMatch($index)
	{
		$index = round($index);
		if($index > 0 && $index <= $this->getNumberMatches())
		{
			--$index;
			if(!isset($this->matches[$index]) || $this->matches[$index] === null)
			{
				$nodeList = $this->getXml()->getElementsByTagName('Match');
				$xmlMatch = new DOMDocument('1.0', 'UTF-8');
				$xmlMatch->appendChild($xmlMatch->importNode($nodeList->item($index), true));
				$this->matches[$index] = new HTTeamMatch($xmlMatch);
			}
			return $this->matches[$index];
		}
		return null;
	}

	/**
	 * Return next match
	 *
	 * @return HTTeamMatch
	 */
	public function getNextMatch()
	{
		if(!isset($this->nextMatch) || $this->nextMatch === null)
		{
			$xpath = new DOMXPath($this->getXml());
			$nodeList = $xpath->query('//Status[.="'.self::NOT_PLAYED.'"]');
			$xmlMatch = new DOMDocument('1.0', 'UTF-8');
			$xmlMatch->appendChild($xmlMatch->importNode($nodeList->item(0)->parentNode, true));
			$this->nextMatch = new HTTeamMatch($xmlMatch);
		}
		return $this->nextMatch;
	}

	/**
	 * Return next matches
	 *
	 * @return Array<HTTeamMatch>
	 */
	public function getNextMatches()
	{
		if(!isset($this->nextMatches) || $this->nextMatches === null)
		{
			$xpath = new DOMXPath($this->getXml());
			$nodeList = $xpath->query('//Status[.="'.self::NOT_PLAYED.'"]');
			for($i=0; $i<$nodeList->length; $i++)
			{
				$xmlMatch = new DOMDocument('1.0', 'UTF-8');
				$xmlMatch->appendChild($xmlMatch->importNode($nodeList->item($i)->parentNode, true));
				$this->nextMatches[$i] = new HTTeamMatch($xmlMatch);
			}
		}
		return $this->nextMatches;
	}

	/**
	 * Return last match
	 *
	 * @return HTTeamMatch
	 */
	public function getLastMatch()
	{
		if(!isset($this->lastMatch) || $this->lastMatch === null)
		{
			$xpath = new DOMXPath($this->getXml());
			$nodeList = $xpath->query('//Status[.="'.self::PLAYED.'"]');
			$xmlMatch = new DOMDocument('1.0', 'UTF-8');
			$xmlMatch->appendChild($xmlMatch->importNode($nodeList->item($nodeList->length - 1)->parentNode, true));
			$this->lastMatch = new HTTeamMatch($xmlMatch);
		}
		return $this->lastMatch;
	}

	/**
	 * Return last matches
	 *
	 * @return Array<HTTeamMatch>
	 */
	public function getLastMatches()
	{
		if(!isset($this->lastMatches) || $this->lastMatches === null)
		{
			$xpath = new DOMXPath($this->getXml());
			$nodeList = $xpath->query('//Status[.="'.self::PLAYED.'"]');
			for($i=1; $i<=$nodeList->length; $i++)
			{
				$xmlMatch = new DOMDocument('1.0', 'UTF-8');
				$xmlMatch->appendChild($xmlMatch->importNode($nodeList->item($nodeList->length - $i)->parentNode, true));
				$this->lastMatches[$i] = new HTTeamMatch($xmlMatch);
			}
		}
		return $this->lastMatches;
	}
}
class HTTeamMatch extends HTXml
{
	private $id = null;
	private $homeTeamId = null;
	private $homeTeamName = null;
	private $awayTeamId = null;
	private $awayTeamName = null;
	private $date = null;
	private $type = null;
	private $ordersGiven = null;
	private $status = null;
	const UNAVAILABLE = 'unavailable';

	/**
	 * @param DOMDocument $xml
	 */
	public function __construct($xml)
	{
		$this->xmlText = $xml->saveXML();
		$this->xml = $xml;
	}

	/**
	 * Return match id
	 *
	 * @return Integer
	 */
	public function getId()
	{
		if(!isset($this->id) || $this->id === null)
		{
			$this->id = $this->getXml()->getElementsByTagName('MatchID')->item(0)->nodeValue;
		}
		return $this->id;
	}

	/**
	 * Return home team id
	 *
	 * @return Integer
	 */
	public function getHomeTeamId()
	{
		if(!isset($this->homeTeamId) || $this->homeTeamId === null)
		{
			$this->homeTeamId = $this->getXml()->getElementsByTagName('HomeTeamID')->item(0)->nodeValue;
		}
		return $this->homeTeamId;
	}

	/**
	 * Return home team name
	 *
	 * @return String
	 */
	public function getHomeTeamName()
	{
		if(!isset($this->homeTeamName) || $this->homeTeamName === null)
		{
			$this->homeTeamName = $this->getXml()->getElementsByTagName('HomeTeamName')->item(0)->nodeValue;
		}
		return $this->homeTeamName;
	}

	/**
	 * Return away team id
	 *
	 * @return Integer
	 */
	public function getAwayTeamId()
	{
		if(!isset($this->awayTeamId) || $this->awayTeamId === null)
		{
			$this->awayTeamId = $this->getXml()->getElementsByTagName('AwayTeamID')->item(0)->nodeValue;
		}
		return $this->awayTeamId;
	}

	/**
	 * Return away team name
	 *
	 * @return String
	 */
	public function getAwayTeamName()
	{
		if(!isset($this->awayTeamName) || $this->awayTeamName === null)
		{
			$this->awayTeamName = $this->getXml()->getElementsByTagName('AwayTeamName')->item(0)->nodeValue;
		}
		return $this->awayTeamName;
	}

	/**
	 * Return match date
	 *
	 * @param String $format (php date() function format)
	 * @return String
	 */
	public function getDate($format = null)
	{
		if(!isset($this->date) || $this->date === null)
		{
			$this->date = $this->getXml()->getElementsByTagName('MatchDate')->item(0)->nodeValue;
			if($format !== null)
			{
				$this->date = HTFunction::convertDate($this->date, $format);
			}
		}
		return $this->date;
	}

	/**
	 * Return match type code
	 *
	 * @return Integer
	 */
	public function getType()
	{
		if(!isset($this->type) || $this->type === null)
		{
			$this->type = $this->getXml()->getElementsByTagName('MatchType')->item(0)->nodeValue;
		}
		return $this->type;
	}

	/**
	 * Is orders already given for this match ?
	 *
	 * @return Boolean
	 */
	public function isOrdersGiven()
	{
		if($this->getStatus() == HTTeamMatches::NOT_PLAYED )
		{
			if(!isset($this->ordersGiven) || $this->ordersGiven === null)
			{
				$node = $this->getXml()->getElementsByTagName('OrdersGiven');
				if($node !== null && $node->length)
				{
					$this->ordersGiven = strtolower($node->item(0)->nodeValue) == "true";
				}
				else
				{
					$this->ordersGiven = self::UNAVAILABLE ;
				}
			}
			if($this->ordersGiven === self::UNAVAILABLE )
			{
				return null;
			}
			return $this->ordersGiven;
		}
		return null;
	}

	/**
	 * Return match status : UPCOMING, ONGOING, FINISHED
	 *
	 * @return String
	 */
	public function getStatus()
	{
		if(!isset($this->status) || $this->status === null)
		{
			$this->status = $this->date = $this->getXml()->getElementsByTagName('Status')->item(0)->nodeValue;
		}
		return $this->status;
	}
}
class HTMatch extends HTGlobal
{
	private $id = null;
	private $type = null;
	private $startDate = null;
	private $endDate = null;
	private $homeTeam = null;
	private $awayTeam = null;
	private $arena = null;
	private $score = null;
	private $goals = null;
	private $totalGoals = null;
	private $cards = null;
	private $totalYellowCards = null;
	private $yellowCards = null;
	private $totalRedCards = null;
	private $redCards = null;
	private $possesionHomeTeamFirstHalf = null;
	private $possesionHomeTeamSecondHalf = null;
	private $possesionAwayTeamFirstHalf = null;
	private $possesionAwayTeamSecondHalf = null;
	private $fullText = null;
	private $firstHalf = null;
	private $secondHalf = null;
	private $eventNumber = null;
	private $events = null;

	/**
	 * Return match id
	 *
	 * @return Integer
	 */
	public function getId()
	{
		if(!isset($this->id) || $this->id === null)
		{
			$this->id = $this->getXml()->getElementsByTagName('MatchID')->item(0)->nodeValue;
		}
		return $this->id;
	}

	/**
	 * Return match type
	 *
	 * @return Integer
	 */
	public function getType()
	{
		if(!isset($this->type) || $this->type === null)
		{
			$this->type = $this->getXml()->getElementsByTagName('MatchType')->item(0)->nodeValue;
		}
		return $this->type;
	}

	/**
	 * Return match start datetime
	 *
	 * @param String $format (php date() function format)
	 * @return String
	 */
	public function getStartDate($format = null)
	{
		if(!isset($this->startDate) || $this->startDate === null)
		{
			$this->startDate = $this->getXml()->getElementsByTagName('MatchDate')->item(0)->nodeValue;
			if($format !== null)
			{
				$this->startDate = HTFunction::convertDate($this->startDate, $format);
			}
		}
		return $this->startDate;
	}

	/**
	 * Return match end datetime
	 *
	 * @param String $format (php date() function format)
	 * @return String
	 */
	public function getEndDate($format = null)
	{
		if(!isset($this->endDate) || $this->endDate === null)
		{
			$this->endDate = $this->getXml()->getElementsByTagName('FinishedDate')->item(0)->nodeValue;
			if($format !== null)
			{
				$this->endDate = HTFunction::convertDate($this->endDate, $format);
			}
		}
		return $this->endDate;
	}

	/**
	 * Return home team HTMatchTeam object
	 *
	 * @return HTMatchTeam
	 */
	public function getHomeTeam()
	{
		if(!isset($this->homeTeam) || $this->homeTeam === null)
		{
			$this->homeTeam = $this->getTeam('Home');
		}
		return $this->homeTeam;
	}

	/**
	 * Return away team HTMatchTeam object
	 *
	 * @return HTMatchTeam
	 */
	public function getAwayTeam()
	{
		if(!isset($this->awayTeam) || $this->awayTeam === null)
		{
			$this->awayTeam = $this->getTeam('Away');
		}
		return $this->awayTeam;
	}

	/**
	 * @param String $type
	 * @return HTMatchTeam
	 */
	private function getTeam($type)
	{
		$xpath = new DOMXPath($this->getXml());
		$nodeList = $xpath->query('//'.$type.'Team');
		$team = new DOMDocument('1.0', 'UTF-8');
		$team->appendChild($team->importNode($nodeList->item(0), true));
		return new HTMatchTeam($team, $type);
	}

	/**
	 * Return HTMatchArena object
	 *
	 * @return HTMatchArena
	 */
	public function getArena()
	{
		if(!isset($this->arena) || $this->arena === null)
		{
			$xpath = new DOMXPath($this->getXml());
			$nodeList = $xpath->query('//Arena');
			$arena = new DOMDocument('1.0', 'UTF-8');
			$arena->appendChild($arena->importNode($nodeList->item(0), true));
			$this->arena = new HTMatchArena($arena);
		}
		return $this->arena;
	}

	/**
	 * Return match score
	 *
	 * @return String
	 */
	public function getScore()
	{
		if(!isset($this->score) || $this->score === null)
		{
			$home = $this->getXml()->getElementsByTagName('HomeGoals')->item(0)->nodeValue;
			$away = $this->getXml()->getElementsByTagName('AwayGoals')->item(0)->nodeValue;
			$this->score = $home.'-'.$away;
		}
		return $this->score;
	}

	/**
	 * Return number of goals in the match
	 *
	 * @return Integer
	 */
	public function getTotalGoals()
	{
		if(!isset($this->totalGoals) || $this->totalGoals === null)
		{
			$xpath = new DOMXPath($this->getXml());
			$nodeList = $xpath->query('//Goal');
			$this->totalGoals = $nodeList->length;
		}
		return $this->totalGoals;
	}

	/**
	 * Return HTMatchGoal object
	 *
	 * @param Integer $number
	 * @return HTMatchGoal
	 */
	public function getGoal($number)
	{
		$number = round($number);
		if($number > 0 && $number <= $this->getTotalGoals())
		{
			--$number;
			if(!isset($this->goals[$number]) || $this->goals[$number] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//Goal');
				$goal = new DOMDocument('1.0', 'UTF-8');
				$goal->appendChild($goal->importNode($nodeList->item($number), true));
				$this->goals[$number] = new HTMatchGoal($goal);
			}
			return $this->goals[$number];
		}
		return null;
	}

	/**
	 * Return total of yellow Cards
	 *
	 * @return Integer
	 */
	public function getTotalYellowCards()
	{
		if(!isset($this->totalYellowCards) || $this->totalYellowCards === null)
		{
			$xpath = new DOMXPath($this->getXml());
			$nodeList = $xpath->query('//BookingType[.="1"]');
			$this->totalYellowCards = $nodeList->length;
		}
		return $this->totalYellowCards;
	}

	/**
	 * Return total of red Cards
	 *
	 * @return Integer
	 */
	public function getTotalRedCards()
	{
		if(!isset($this->totalRedCards) || $this->totalRedCards === null)
		{
			$xpath = new DOMXPath($this->getXml());
			$nodeList = $xpath->query('//BookingType[.="2"]');
			$this->totalRedCards = $nodeList->length;
		}
		return $this->totalRedCards;
	}

	/**
	 * Return total of Cards
	 *
	 * @return Integer
	 */
	public function getTotalCards()
	{
		return $this->getTotalRedCards()+$this->getTotalYellowCards();
	}

	/**
	 * Return HTMatchCard object
	 *
	 * @return HTMatchCard
	 */
	public function getCard($number)
	{
		$number = round($number);
		if($number > 0 && $number <= $this->getTotalCards())
		{
			--$number;
			if(!isset($this->cards[$number]) || $this->cards[$number] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//Booking');
				$card = new DOMDocument('1.0', 'UTF-8');
				$card->appendChild($card->importNode($nodeList->item($number), true));
				$this->cards[$number] = new HTMatchCard($card);
			}
			return $this->cards[$number];
		}
		return null;
	}

	/**
	 * Return yellow HTMatchCard object
	 *
	 * @return HTMatchCard
	 */
	public function getYellowCard($number)
	{
		$number = round($number);
		if($number > 0 && $number <= $this->getTotalYellowCards())
		{
			if(!isset($this->yellowCards[$number]) || $this->yellowCards[$number] === null)
			{
				$this->yellowCards[$number] = $this->getColorCard(1, $number);
			}
			return $this->yellowCards[$number];
		}
		return null;
	}

	/**
	 * Return red HTMatchCard object
	 *
	 * @return HTMatchCard
	 */
	public function getRedCard($number)
	{
		$number = round($number);
		if($number > 0 && $number <= $this->getTotalRedCards())
		{
			if(!isset($this->redCards[$number]) || $this->redCards[$number] === null)
			{
				$this->redCards[$number] = $this->getColorCard(2, $number);
			}
			return $this->yellowCards[$number];
		}
		return null;
	}

	/**
	 * @return HTMatchCard
	 */
	private function getColorCard($colorId, $number)
	{
		$xpath = new DOMXPath($this->getXml());
		$nodeList = $xpath->query('//BookingType[.="'.$colorId.'"]');
		$card = new DOMDocument('1.0', 'UTF-8');
		$card->appendChild($card->importNode($nodeList->item((--$number))->parentNode, true));
		return  new HTMatchCard($card);
	}

	/**
	 * Return home team possession in firt halftime
	 *
	 * @return Integer
	 */
	public function getHomeTeamPossessionFirstHalf()
	{
		if(!isset($this->possesionHomeTeamFirstHalf) || $this->possesionHomeTeamFirstHalf === null)
		{
			$this->possesionHomeTeamFirstHalf = $this->getXml()->getElementsByTagName('PossessionFirstHalfHome')->item(0)->nodeValue;
		}
		return $this->possesionHomeTeamFirstHalf;
	}

	/**
	 * Return home team possession in second halftime
	 *
	 * @return Integer
	 */
	public function getHomeTeamPossessionSecondHalf()
	{
		if(!isset($this->possesionHomeTeamSecondHalf) || $this->possesionHomeTeamSecondHalf === null)
		{
			$this->possesionHomeTeamSecondHalf = $this->getXml()->getElementsByTagName('PossessionSecondHalfHome')->item(0)->nodeValue;
		}
		return $this->possesionHomeTeamSecondHalf;
	}

	/**
	 * Return away team possession in firt halftime
	 *
	 * @return Integer
	 */
	public function getAwayTeamPossessionFirstHalf()
	{
		if(!isset($this->possesionAwayTeamFirstHalf) || $this->possesionAwayTeamFirstHalf === null)
		{
			$this->possesionAwayTeamFirstHalf = $this->getXml()->getElementsByTagName('PossessionFirstHalfAway')->item(0)->nodeValue;
		}
		return $this->possesionAwayTeamFirstHalf;
	}

	/**
	 * Return away team possession in second halftime
	 *
	 * @return Integer
	 */
	public function getAwayTeamPossessionSecondHalf()
	{
		if(!isset($this->possesionAwayTeamSecondHalf) || $this->possesionAwayTeamSecondHalf === null)
		{
			$this->possesionAwayTeamSecondHalf = $this->getXml()->getElementsByTagName('PossessionSecondHalfAway')->item(0)->nodeValue;
		}
		return $this->possesionAwayTeamSecondHalf;
	}

	/**
	 * Return whole text match
	 *
	 * @param String $playerUrlReplacement (given url is concat with : PlayerID=xxxxxxx )
	 * @return String
	 */
	public function getFullText($playerUrlReplacement = null)
	{
		if(!isset($this->fullText) || $this->fullText === null)
		{
			$this->fullText = $this->getFirstHalfTime($playerUrlReplacement).$this->getSecondHalfTime($playerUrlReplacement);
		}
		return $this->fullText;
	}

	/**
	 * Return text of first half time
	 *
	 * @param String $playerUrlReplacement (given url is concat with : PlayerID=xxxxxxx )
	 * @return String
	 */
	public function getFirstHalfTime($playerUrlReplacement = null)
	{
		if(!isset($this->firstHalf) || $this->firstHalf === null)
		{
			$xpath = new DOMXPath($this->getXml());
			$nodeList = $xpath->query('//Minute[.<46]');
			$text = '';
			foreach ($nodeList as $event)
			{
				$event = $event->parentNode;
				$text .= $event->getElementsByTagName('EventText')->item(0)->nodeValue;
			}
			$this->firstHalf = $this->replaceUrl($text, $playerUrlReplacement);
		}
		return $this->firstHalf;
	}

	/**
	 * Return text of second half time
	 *
	 * @param String $playerUrlReplacement (given url is concat with : PlayerID=xxxxxxx )
	 * @return String
	 */
	public function getSecondHalfTime($playerUrlReplacement = null)
	{
		if(!isset($this->secondHalf) || $this->secondHalf === null)
		{
			$xpath = new DOMXPath($this->getXml());
			$nodeList = $xpath->query('//Minute[.>45]');
			$text = '';
			foreach ($nodeList as $event)
			{
				$event = $event->parentNode;
				$text .= $event->getElementsByTagName('EventText')->item(0)->nodeValue;
			}
			$this->secondHalf = $this->replaceUrl($text, $playerUrlReplacement);
		}
		return $this->secondHalf;
	}

	/**
	 * @param String $text
	 * @param String $playerUrlReplacement
	 * @return String
	 */
	private function replaceUrl($text, $playerUrlReplacement)
	{
		if($playerUrlReplacement !== null)
		{
			$text = HTFunction::updatePlayerUrl($text, $playerUrlReplacement);
		}
		else
		{
			$text = HTFunction::updatePlayerUrl($text, CHPPConnection::SERVER_URL . HTFunction::PLAYERURL);
		}
		return $text;
	}

	/**
	 * Return number event
	 *
	 * @return Integer
	 */
	public function getEventNumber()
	{
		if(!isset($this->eventNumber) || $this->eventNumber === null)
		{
			$xpath = new DOMXPath($this->getXml());
			$nodeList = $xpath->query('//Event');
			$this->eventNumber = $nodeList->length;
		}
		return $this->eventNumber;
	}

	/**
	 * Return HTLiveMatchEvent object
	 *
	 * @param Integer $number
	 * @return HTLiveMatchEvent
	 */
	public function getEvent($number)
	{
		$number = round($number);
		if($number > 0 && $number <= $this->getEventNumber())
		{
			--$number;
			if(!isset($this->events[$number]) || $this->events[$number] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//Event');
				$event = new DOMDocument('1.0', 'UTF-8');
				$event->appendChild($event->importNode($nodeList->item($number), true));
				$this->events[$number] = new HTLiveMatchEvent($event);
			}
			return $this->events[$number];
		}
		return null;
	}
}
class HTMatchTeam extends HTXml
{
	private $type;
	private $id = null;
	private $name = null;
	private $dressCode = null;
	private $goals = null;
	private $tacticType = null;
	private $tacticSkill = null;
	private $midfield = null;
	private $rightDef = null;
	private $midDef = null;
	private $leftDef = null;
	private $rightAtt = null;
	private $midAtt = null;
	private $leftAtt = null;
	private $attitude = null;
	private $formation = null;
	const UNAVAILABLE = 'unavailable';

	/**
	 * @param DOMDocument $xml
	 */
	public function __construct($xml, $type)
	{
		$this->xmlText = $xml->saveXML();
		$this->xml = $xml;
		$this->type = $type;
	}

	/**
	 * Return team id
	 *
	 * @return Integer
	 */
	public function getId()
	{
		if(!isset($this->id) || $this->id === null)
		{
			$this->id = $this->getXml()->getElementsByTagName($this->type.'TeamID')->item(0)->nodeValue;
		}
		return $this->id;
	}

	/**
	 * Return team name
	 *
	 * @return String
	 */
	public function getName()
	{
		if(!isset($this->name) || $this->name === null)
		{
			$this->name = $this->getXml()->getElementsByTagName($this->type.'TeamName')->item(0)->nodeValue;
		}
		return $this->name;
	}

	/**
	 * Return dress code
	 *
	 * @return String
	 */
	public function getDressCode()
	{
		if(!isset($this->dressCode) || $this->dressCode === null)
		{
			$this->dressCode = $this->getXml()->getElementsByTagName('Dress')->item(0)->nodeValue;
		}
		return $this->dressCode;
	}

	/**
	 * Return goals number
	 *
	 * @return Integer
	 */
	public function getGoals()
	{
		if(!isset($this->goals) || $this->goals === null)
		{
			$this->goals = $this->getXml()->getElementsByTagName($this->type.'Goals')->item(0)->nodeValue;
		}
		return $this->goals;
	}

	/**
	 * Return tactic type
	 *
	 * @return Integer
	 */
	public function getTacticType()
	{
		if(!isset($this->tacticType) || $this->tacticType === null)
		{
			$this->tacticType = $this->getXml()->getElementsByTagName('TacticType')->item(0)->nodeValue;
		}
		return $this->tacticType;
	}

	/**
	 * Return tactic skill
	 *
	 * @return Integer
	 */
	public function getTacticSkill()
	{
		if(!isset($this->tacticSkill) || $this->tacticSkill === null)
		{
			$this->tacticSkill = $this->getXml()->getElementsByTagName('TacticSkill')->item(0)->nodeValue;
		}
		return $this->tacticSkill;
	}

	/**
	 * Return miedfield rating
	 *
	 * @deprecated use getMidfieldRating() function
	 * @return Integer
	 */
	public function getMiedfieldRating()
	{
		return $this->getMidfieldRating();
	}

	/**
	 * Return miedfield rating
	 *
	 * @return Integer
	 */
	public function getMidfieldRating()
	{
		if(!isset($this->midfield) || $this->midfield === null)
		{
			$this->midfield = $this->getXml()->getElementsByTagName('RatingMidfield')->item(0)->nodeValue;
		}
		return $this->midfield;
	}

	/**
	 * Return right defense rating
	 *
	 * @return Integer
	 */
	public function getRightDefenseRating()
	{
		if(!isset($this->rightDef) || $this->rightDef === null)
		{
			$this->rightDef = $this->getXml()->getElementsByTagName('RatingRightDef')->item(0)->nodeValue;
		}
		return $this->rightDef;
	}

	/**
	 * Return central defense rating
	 *
	 * @return Integer
	 */
	public function getCentralDefenseRating()
	{
		if(!isset($this->midDef) || $this->midDef === null)
		{
			$this->midDef = $this->getXml()->getElementsByTagName('RatingMidDef')->item(0)->nodeValue;
		}
		return $this->midDef;
	}

	/**
	 * Return left defense rating
	 *
	 * @return Integer
	 */
	public function getLeftDefenseRating()
	{
		if(!isset($this->leftDef) || $this->leftDef === null)
		{
			$this->leftDef = $this->getXml()->getElementsByTagName('RatingLeftDef')->item(0)->nodeValue;
		}
		return $this->leftDef;
	}

	/**
	 * Return right attack rating
	 *
	 * @return Integer
	 */
	public function getRightAttackRating()
	{
		if(!isset($this->rightAtt) || $this->rightAtt === null)
		{
			$this->rightAtt = $this->getXml()->getElementsByTagName('RatingRightAtt')->item(0)->nodeValue;
		}
		return $this->rightAtt;
	}

	/**
	 * Return central attack rating
	 *
	 * @return Integer
	 */
	public function getCentralAttackRating()
	{
		if(!isset($this->midAtt) || $this->midAtt === null)
		{
			$this->midAtt = $this->getXml()->getElementsByTagName('RatingMidAtt')->item(0)->nodeValue;
		}
		return $this->midAtt;
	}

	/**
	 * Return left attack rating
	 *
	 * @return Integer
	 */
	public function getLeftAttackRating()
	{
		if(!isset($this->leftAtt) || $this->leftAtt === null)
		{
			$this->leftAtt = $this->getXml()->getElementsByTagName('RatingLeftAtt')->item(0)->nodeValue;
		}
		return $this->leftAtt;
	}

	/**
	 * Return HatStats note
	 *
	 * @return Integer
	 */
	public function getHatStats()
	{
		return ($this->getMiedfieldRating()*3)+
						$this->getRightDefenseRating()+
						$this->getLeftDefenseRating()+
						$this->getCentralDefenseRating()+
						$this->getRightAttackRating()+
						$this->getLeftAttackRating()+
						$this->getCentralAttackRating();
	}

	/**
	 * Return match attitude if given
	 *
	 * @return Integer
	 */
	public function getAttitude()
	{
		if(!isset($this->attitude) || $this->attitude === null)
		{
			$node = $this->getXml()->getElementsByTagName('TeamAttitude');
			if($node !== null && $node->length)
			{
				$this->attitude = $node->item(0)->nodeValue;
			}
			else
			{
				$this->attitude = self::UNAVAILABLE ;
			}
		}
		if($this->attitude === self::UNAVAILABLE )
		{
			return null;
		}
		return $this->attitude;
	}

	/**
	 * Return team formation
	 *
	 * @return String
	 */
	public function getFormation()
	{
		if(!isset($this->formation) || $this->formation === null)
		{
			$this->formation = $this->getXml()->getElementsByTagName('Formation')->item(0)->nodeValue;
		}
		return $this->formation;
	}
}
class HTMatchArena extends HTXml
{
	private $id = null;
	private $name = null;
	private $wheatherId = null;
	private $spectators = null;

	/**
	 * @param DOMDocument $xml
	 */
	public function __construct($xml)
	{
		$this->xmlText = $xml->saveXML();
		$this->xml = $xml;
	}

	/**
	 * Return arena id
	 *
	 * @return Integer
	 */
	public function getId()
	{
		if(!isset($this->id) || $this->id === null)
		{
			$this->id = $this->getXml()->getElementsByTagName('ArenaID')->item(0)->nodeValue;
		}
		return $this->id;
	}

	/**
	 * Return arena name
	 *
	 * @return String
	 */
	public function getName()
	{
		if(!isset($this->name) || $this->name === null)
		{
			$this->name = $this->getXml()->getElementsByTagName('ArenaName')->item(0)->nodeValue;
		}
		return $this->name;
	}

	/**
	 * Return weather id
	 *
	 * @return Integer
	 */
	public function getWeatherId()
	{
		if(!isset($this->wheatherId) || $this->wheatherId === null)
		{
			$this->wheatherId = $this->getXml()->getElementsByTagName('WeatherID')->item(0)->nodeValue;
		}
		return $this->wheatherId;
	}

	/**
	 * Return spectators number
	 *
	 * @return Integer
	 */
	public function getSpectators()
	{
		if(!isset($this->spectators) || $this->spectators === null)
		{
			$this->spectators = $this->getXml()->getElementsByTagName('SoldTotal')->item(0)->nodeValue;
		}
		return $this->spectators;
	}
}
class HTMatchGoal extends HTXml
{
	private $scorerId = null;
	private $scorerName = null;
	private $scorerTeamId = null;
	private $scoreHomeTeam = null;
	private $scoreAwayTeam = null;
	private $minute = null;

	/**
	 * @param DOMDocument $xml
	 */
	public function __construct($xml)
	{
		$this->xmlText = $xml->saveXML();
		$this->xml = $xml;
	}

	/**
	 * Return scorer player id
	 *
	 * @return Integer
	 */
	public function getScorerId()
	{
		if(!isset($this->scorerId) || $this->scorerId === null)
		{
			$this->scorerId = $this->getXml()->getElementsByTagName('ScorerPlayerID')->item(0)->nodeValue;
		}
		return $this->scorerId;
	}

	/**
	 * Return scorer player name
	 *
	 * @return String
	 */
	public function getScorerName()
	{
		if(!isset($this->scorerName) || $this->scorerName === null)
		{
			$this->scorerName = $this->getXml()->getElementsByTagName('ScorerPlayerName')->item(0)->nodeValue;
		}
		return $this->scorerName;
	}

	/**
	 * Return scorer team id
	 *
	 * @return Integer
	 */
	public function getScorerTeamId()
	{
		if(!isset($this->scorerTeamId) || $this->scorerTeamId === null)
		{
			$this->scorerTeamId = $this->getXml()->getElementsByTagName('ScorerTeamID')->item(0)->nodeValue;
		}
		return $this->scorerTeamId;
	}

	/**
	 * Return home team score
	 *
	 * @return Integer
	 */
	public function getHomeTeamScore()
	{
		if(!isset($this->scoreHomeTeam) || $this->scoreHomeTeam === null)
		{
			$this->scoreHomeTeam = $this->getXml()->getElementsByTagName('ScorerHomeGoals')->item(0)->nodeValue;
		}
		return $this->scoreHomeTeam;
	}

	/**
	 * Return away team score
	 *
	 * @return Integer
	 */
	public function getAwayTeamScore()
	{
		if(!isset($this->scoreAwayTeam) || $this->scoreAwayTeam === null)
		{
			$this->scoreAwayTeam = $this->getXml()->getElementsByTagName('ScorerAwayGoals')->item(0)->nodeValue;
		}
		return $this->scoreAwayTeam;
	}

	/**
	 * Return goal minute
	 *
	 * @return Integer
	 */
	public function getMinute()
	{
		if(!isset($this->minute) || $this->minute === null)
		{
			$this->minute = $this->getXml()->getElementsByTagName('ScorerMinute')->item(0)->nodeValue;
		}
		return $this->minute;
	}
}
class HTMatchCard extends HTXml
{
	private $playerId = null;
	private $playerName = null;
	private $teamId = null;
	private $type = null;
	private $minute = null;

	/**
	 * @param DOMDocument $xml
	 */
	public function __construct($xml)
	{
		$this->xmlText = $xml->saveXML();
		$this->xml = $xml;
	}

	/**
	 * Return card's player id
	 *
	 * @return Integer
	 */
	public function getPlayerid()
	{
		if(!isset($this->playerId) || $this->playerId === null)
		{
			$this->playerId = $this->getXml()->getElementsByTagName('BookingPlayerID')->item(0)->nodeValue;
		}
		return $this->playerId;
	}

	/**
	 * Return card's player name
	 *
	 * @return String
	 */
	public function getPlayerName()
	{
		if(!isset($this->playerName) || $this->playerName === null)
		{
			$this->playerName = $this->getXml()->getElementsByTagName('BookingPlayerName')->item(0)->nodeValue;
		}
		return $this->playerName;
	}

	/**
	 * Return card's team id
	 *
	 * @return Integer
	 */
	public function getTeamId()
	{
		if(!isset($this->teamId) || $this->teamId === null)
		{
			$this->teamId = $this->getXml()->getElementsByTagName('BookingTeamID')->item(0)->nodeValue;
		}
		return $this->teamId;
	}

	/**
	 * Return card type
	 *
	 * @return Integer
	 */
	public function getType()
	{
		if(!isset($this->type) || $this->type === null)
		{
			$this->type = $this->getXml()->getElementsByTagName('BookingType')->item(0)->nodeValue;
		}
		return $this->type;
	}

	/**
	 * Return card minute
	 *
	 * @return Integer
	 */
	public function getMinute()
	{
		if(!isset($this->minute) || $this->minute === null)
		{
			$this->minute = $this->getXml()->getElementsByTagName('BookingMinute')->item(0)->nodeValue;
		}
		return $this->minute;
	}
}
class HTMatchArchive extends HTCommonTeam
{
	private $matchNumber = null;
	private $matches = null;

	/**
	 * Return number of match in list
	 *
	 * @return Integer
	 */
	public function getMatchNumber()
	{
		if(!isset($this->matchNumber) || $this->matchNumber === null)
		{
			$xpath = new DOMXPath($this->getXml());
			$nodeList = $xpath->query('//Match');
			$this->matchNumber = $nodeList->length;
		}
		return $this->matchNumber;
	}

	/**
	 * Return HTMatchArchiveMatch object
	 *
	 * @param Integer $index
	 * @return HTMatchArchiveMatch
	 */
	public function getMatch($index)
	{
		$index = round($index);
		if($index > 0 && $index <= $this->getMatchNumber())
		{
			--$index;
			if(!isset($this->matches[$index]) || $this->matches[$index] === null)
			{
				$nodeList = $this->getXml()->getElementsByTagName('Match');
				$xmlMatch = new DOMDocument('1.0', 'UTF-8');
				$xmlMatch->appendChild($xmlMatch->importNode($nodeList->item($index), true));
				$this->matches[$index] = new HTMatchArchiveMatch($xmlMatch);
			}
			return $this->matches[$index];
		}
		return null;
	}
}
class HTMatchArchiveMatch extends HTXml
{
	private $id = null;
	private $homeTeamId = null;
	private $homeTeamName = null;
	private $awayTeamId = null;
	private $awayTeamName = null;
	private $date = null;
	private $type = null;
	private $homeGoals = null;
	private $awayGoals = null;

	/**
	 * @param DOMDocument $xml
	 */
	public function __construct($xml)
	{
		$this->xmlText = $xml->saveXML();
		$this->xml = $xml;
	}

	/**
	 * Return match id
	 *
	 * @return Integer
	 */
	public function getId()
	{
		if(!isset($this->id) || $this->id === null)
		{
			$this->id = $this->getXml()->getElementsByTagName('MatchID')->item(0)->nodeValue;
		}
		return $this->id;
	}

	/**
	 * Return home team id
	 *
	 * @return Integer
	 */
	public function getHomeTeamId()
	{
		if(!isset($this->homeTeamId) || $this->homeTeamId === null)
		{
			$this->homeTeamId = $this->getXml()->getElementsByTagName('HomeTeamID')->item(0)->nodeValue;
		}
		return $this->homeTeamId;
	}

	/**
	 * Return home team name
	 *
	 * @return String
	 */
	public function getHomeTeamName()
	{
		if(!isset($this->homeTeamName) || $this->homeTeamName === null)
		{
			$this->homeTeamName = $this->getXml()->getElementsByTagName('HomeTeamName')->item(0)->nodeValue;
		}
		return $this->homeTeamName;
	}

	/**
	 * Return away team id
	 *
	 * @return Integer
	 */
	public function getAwayTeamId()
	{
		if(!isset($this->awayTeamId) || $this->awayTeamId === null)
		{
			$this->awayTeamId = $this->getXml()->getElementsByTagName('AwayTeamID')->item(0)->nodeValue;
		}
		return $this->awayTeamId;
	}

	/**
	 * Return away team name
	 *
	 * @return String
	 */
	public function getAwayTeamName()
	{
		if(!isset($this->awayTeamName) || $this->awayTeamName === null)
		{
			$this->awayTeamName = $this->getXml()->getElementsByTagName('AwayTeamName')->item(0)->nodeValue;
		}
		return $this->awayTeamName;
	}

	/**
	 * Return match date
	 *
	 * @param String $format (php date() function format)
	 * @return String
	 */
	public function getDate($format = null)
	{
		if(!isset($this->date) || $this->date === null)
		{
			$this->date = $this->getXml()->getElementsByTagName('MatchDate')->item(0)->nodeValue;
			if($format !== null)
			{
				$this->date = HTFunction::convertDate($this->date, $format);
			}
		}
		return $this->date;
	}

	/**
	 * Return match type
	 *
	 * @return Integer
	 */
	public function getType()
	{
		if(!isset($this->type) || $this->type === null)
		{
			$this->type = $this->getXml()->getElementsByTagName('MatchType')->item(0)->nodeValue;
		}
		return $this->type;
	}

	/**
	 * Return home goals
	 *
	 * @return Integer
	 */
	public function getHomeGoals()
	{
		if(!isset($this->homeGoals) || $this->homeGoals === null)
		{
			$this->homeGoals = $this->getXml()->getElementsByTagName('HomeGoals')->item(0)->nodeValue;
		}
		return $this->homeGoals;
	}

	/**
	 * Return away goals
	 *
	 * @return Integer
	 */
	public function getAwayGoals()
	{
		if(!isset($this->awayGoals) || $this->awayGoals === null)
		{
			$this->awayGoals = $this->getXml()->getElementsByTagName('AwayGoals')->item(0)->nodeValue;
		}
		return $this->awayGoals;
	}

	/**
	 * Return score
	 *
	 * @return String
	 */
	public function getScore()
	{
		return $this->getHomeGoals().'-'.$this->getAwayGoals();
	}
}
class HTLineup extends HTCommonTeam
{
	private $matchId = null;
	private $matchType = null;
	private $matchDate = null;
	private $homeTeamId = null;
	private $homeTeamName = null;
	private $awayTeamId = null;
	private $awayTeamName = null;
	private $arenaId = null;
	private $arenaName = null;
	private $teamExperienceLevel = null;
	private $playersNumber = null;
	private $players = null;
	private $totalStars = null;
	private $totalStarsEndOfMatch = null;

	/**
	 * Return match id
	 *
	 * @return Integer
	 */
	public function getMatchId()
	{
		if(!isset($this->matchId) || $this->matchId === null)
		{
			$this->matchId = $this->getXml()->getElementsByTagName('MatchID')->item(0)->nodeValue;
		}
		return $this->matchId;
	}

	/**
	 * Return home team id
	 *
	 * @return Integer
	 */
	public function getHomeTeamId()
	{
		if(!isset($this->homeTeamId) || $this->homeTeamId === null)
		{
			$this->homeTeamId = $this->getXml()->getElementsByTagName('HomeTeamID')->item(0)->nodeValue;
		}
		return $this->homeTeamId;
	}

	/**
	 * Return home team name
	 *
	 * @return String
	 */
	public function getHomeTeamName()
	{
		if(!isset($this->homeTeamName) || $this->homeTeamName === null)
		{
			$this->homeTeamName = $this->getXml()->getElementsByTagName('HomeTeamName')->item(0)->nodeValue;
		}
		return $this->homeTeamName;
	}

	/**
	 * Return away team id
	 *
	 * @return Integer
	 */
	public function getAwayTeamId()
	{
		if(!isset($this->awayTeamId) || $this->awayTeamId === null)
		{
			$this->awayTeamId = $this->getXml()->getElementsByTagName('AwayTeamID')->item(0)->nodeValue;
		}
		return $this->awayTeamId;
	}

	/**
	 * Return away team name
	 *
	 * @return String
	 */
	public function getAwayTeamName()
	{
		if(!isset($this->awayTeamName) || $this->awayTeamName === null)
		{
			$this->awayTeamName = $this->getXml()->getElementsByTagName('AwayTeamName')->item(0)->nodeValue;
		}
		return $this->awayTeamName;
	}

	/**
	 * Return match date
	 *
	 * @param String $format (php date() function format)
	 * @return String
	 */
	public function getMatchDate($format = null)
	{
		if(!isset($this->matchDate) || $this->matchDate === null)
		{
			$this->matchDate = $this->getXml()->getElementsByTagName('MatchDate')->item(0)->nodeValue;
			if($format !== null)
			{
				$this->matchDate = HTFunction::convertDate($this->matchDate, $format);
			}
		}
		return $this->matchDate;
	}

	/**
	 * Return match type
	 *
	 * @return Integer
	 */
	public function getMatchType()
	{
		if(!isset($this->matchType) || $this->matchType === null)
		{
			$this->matchType = $this->getXml()->getElementsByTagName('MatchType')->item(0)->nodeValue;
		}
		return $this->matchType;
	}

	/**
	 * Return arena id
	 *
	 * @return Integer
	 */
	public function getArenaId()
	{
		if(!isset($this->arenaId) || $this->arenaId === null)
		{
			$this->arenaId = $this->getXml()->getElementsByTagName('ArenaID')->item(0)->nodeValue;
		}
		return $this->arenaId;
	}

	/**
	 * Return arena name
	 *
	 * @return String
	 */
	public function getArenaName()
	{
		if(!isset($this->arenaName) || $this->arenaName === null)
		{
			$this->arenaName = $this->getXml()->getElementsByTagName('ArenaName')->item(0)->nodeValue;
		}
		return $this->arenaName;
	}

	/**
	 * Return team experience level
	 *
	 * @return Integer
	 */
	public function getTeamExperience()
	{
		if(!isset($this->teamExperienceLevel) || $this->teamExperienceLevel === null)
		{
			$this->teamExperienceLevel = $this->getXml()->getElementsByTagName('ExperienceLevel')->item(0)->nodeValue;
		}
		return $this->teamExperienceLevel;
	}

	/**
	 * Return number of players on lineup
	 *
	 * @return Integer
	 */
	public function getPlayersNumber()
	{
		if(!isset($this->playersNumber) || $this->playersNumber === null)
		{
			$xpath = new DOMXPath($this->getXml());
			$nodeList = $xpath->query('//Player');
			$this->playersNumber = $nodeList->length;
		}
		return $this->playersNumber;
	}

	/**
	 * Return HTLineupPlayer object
	 *
	 * @param Integer $index
	 * @return HTLineupPlayer
	 */
	public function getPlayer($index)
	{
		$index = round($index);
		if($index > 0 && $index <= $this->getPlayersNumber())
		{
			--$index;
			if(!isset($this->players[$index]) || $this->players[$index] === null)
			{
				$nodeList = $this->getXml()->getElementsByTagName('Player');
				$player = new DOMDocument('1.0', 'UTF-8');
				$player->appendChild($player->importNode($nodeList->item($index), true));
				$this->players[$index] = new HTLineupPlayer($player);
			}
			return $this->players[$index];
		}
		return null;
	}

	/**
	 * Return team total stars
	 *
	 * @return Float
	 */
	public function getTotalStars()
	{
		if(!isset($this->totalStars) || $this->totalStars === null)
		{
			$total = 0;
			for($i=1; $i<=$this->getPlayersNumber(); $i++)
			{
				$total += $this->getPlayer($i)->getRatingStars();
			}
			$this->totalStars = $total;
		}
		return $this->totalStars;
	}

	/**
	 * Return team total stars at end of match
	 *
	 * @return Float
	 */
	public function getTotalStarsAtEndOfMatch()
	{
		if(!isset($this->totalStarsEndOfMatch) || $this->totalStarsEndOfMatch === null)
		{
			$total = 0;
			for($i=1; $i<=$this->getPlayersNumber(); $i++)
			{
				$total += $this->getPlayer($i)->getRatingStarsAtEndOfMatch();
			}
			$this->totalStarsEndOfMatch = $total;
		}
		return $this->totalStarsEndOfMatch;
	}
}
class HTLineupPlayer extends HTXml
{
	private $id = null;
	private $roleId = null;
	private $name = null;
	private $ratingStars = null;
	private $ratingStarsEnd = null;
	private $position = null;
	private $individualOrder = null;
	const UNAVAILABLE = 'Not Available';

	/**
	 * @param DOMDocument $xml
	 */
	public function __construct($xml)
	{
		$this->xmlText = $xml->saveXML();
		$this->xml = $xml;
	}

	/**
	 * Return player id
	 *
	 * @return Integer
	 */
	public function getId()
	{
		if(!isset($this->id) || $this->id === null)
		{
			$this->id = $this->getXml()->getElementsByTagName('PlayerID')->item(0)->nodeValue;
		}
		return $this->id;
	}

	/**
	 * Return player role id
	 *
	 * @return Integer
	 */
	public function getRole()
	{
		if(!isset($this->roleId) || $this->roleId === null)
		{
			$this->roleId = $this->getXml()->getElementsByTagName('RoleID')->item(0)->nodeValue;
		}
		return $this->roleId;
	}

	/**
	 * Return player name
	 *
	 * @return String
	 */
	public function getName()
	{
		if(!isset($this->name) || $this->name === null)
		{
			$this->name = $this->getXml()->getElementsByTagName('PlayerName')->item(0)->nodeValue;
		}
		return $this->name;
	}

	/**
	 * Return player rating stars
	 *
	 * @return Float
	 */
	public function getRatingStars()
	{
		if(!isset($this->ratingStars) || $this->ratingStars === null)
		{
			$tmp = $this->getXml()->getElementsByTagName('RatingStars');
			if($tmp->length)
			{
				$this->ratingStars = $tmp->item(0)->nodeValue;
			}
			else
			{
				$this->ratingStars = self::UNAVAILABLE;
			}
		}
		return $this->ratingStars;
	}

	/**
	 * Return player rating stars at end of game
	 *
	 * @return Float
	 */
	public function getRatingStarsAtEndOfMatch()
	{
		if(!isset($this->ratingStarsEnd) || $this->ratingStarsEnd === null)
		{
			$this->ratingStarsEnd = $this->getXml()->getElementsByTagName('RatingStarsEndOfMatch')->item(0)->nodeValue;
		}
		return $this->ratingStarsEnd;
	}

	/**
	 * Return player position code
	 *
	 * @return Integer
	 */
	public function getPosition()
	{
		if(!isset($this->position) || $this->position === null)
		{
			$tmp = $this->getXml()->getElementsByTagName('PositionCode');
			if($tmp->length)
			{
				$this->position = $tmp->item(0)->nodeValue;
			}
			else
			{
				$this->position = self::UNAVAILABLE;
			}
		}
		return $this->position;
	}

	/**
	 * Return player individual order code
	 *
	 * @return Integer
	 */
	public function getIndividualOrder()
	{
		if(!isset($this->individualOrder) || $this->individualOrder === null)
		{
			$tmp = $this->getXml()->getElementsByTagName('Behaviour');
			if($tmp->length)
			{
				$this->individualOrder = $tmp->item(0)->nodeValue;
			}
			else
			{
				$this->individualOrder = self::UNAVAILABLE;
			}
		}
		return $this->individualOrder;
	}
}
class HTLive extends HTGlobal
{
	private $matchNumber = null;
	private $matches = null;

	/**
	 * Return number of match in list
	 *
	 * @return Integer
	 */
	public function getMatchNumber()
	{
		if(!isset($this->matchNumber) || $this->matchNumber === null)
		{
			$xpath = new DOMXPath($this->getXml());
			$nodeList = $xpath->query('//Match');
			$this->matchNumber = $nodeList->length;
		}
		return $this->matchNumber;
	}

	/**
	 * Return HTLiveMatch object
	 *
	 * @param Integer $number
	 * @return HTLiveMatch
	 */
	public function getMatch($number)
	{
		$number = round($number);
		if($number > 0 && $number <= $this->getMatchNumber())
		{
			--$number;
			if(!isset($this->matches[$number]) || $this->matches[$number] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//Match');
				$match = new DOMDocument('1.0', 'UTF-8');
				$match->appendChild($match->importNode($nodeList->item($number), true));
				$this->matches[$number] = new HTLiveMatch($match);
			}
			return $this->matches[$number];
		}
		return null;
	}
}
class HTLiveMatch extends HTXml
{
	private $isYouth = null;
	private $id = null;
	private $date	= null;
	private $homeTeamId = null;
	private $homeTeamName = null;
	private $awayTeamId = null;
	private $awayTeamName = null;
	private $homeGoals = null;
	private $awayGoals = null;
	private $eventNumber = null;
	private $events = null;
	private $eventsIndex = null;

	/**
	 * @param DOMDocument $xml
	 */
	public function __construct($xml)
	{
		$this->xmlText = $xml->saveXML();
		$this->xml = $xml;
	}

	/**
	 * Is the match a youth match ?
	 *
	 * @return Boolean
	 */
	public function isYouth()
	{
		if(!isset($this->isYouth) || $this->isYouth === null)
		{
			$this->isYouth = strtolower($this->getXml()->getElementsByTagName('IsYouth')->item(0)->nodeValue) == "true";
		}
		return $this->isYouth;
	}

	/**
	 * Return match id
	 *
	 * @return Integer
	 */
	public function getId()
	{
		if(!isset($this->id) || $this->id === null)
		{
			$this->id = $this->getXml()->getElementsByTagName('MatchID')->item(0)->nodeValue;
		}
		return $this->id;
	}

	/**
	 * Return home team id
	 *
	 * @return Integer
	 */
	public function getHomeTeamId()
	{
		if(!isset($this->homeTeamId) || $this->homeTeamId === null)
		{
			$this->homeTeamId = $this->getXml()->getElementsByTagName('HomeTeamID')->item(0)->nodeValue;
		}
		return $this->homeTeamId;
	}

	/**
	 * Return home team name
	 *
	 * @return String
	 */
	public function getHomeTeamName()
	{
		if(!isset($this->homeTeamName) || $this->homeTeamName === null)
		{
			$this->homeTeamName = $this->getXml()->getElementsByTagName('HomeTeamName')->item(0)->nodeValue;
		}
		return $this->homeTeamName;
	}

	/**
	 * Return away team id
	 *
	 * @return Integer
	 */
	public function getAwayTeamId()
	{
		if(!isset($this->awayTeamId) || $this->awayTeamId === null)
		{
			$this->awayTeamId = $this->getXml()->getElementsByTagName('AwayTeamID')->item(0)->nodeValue;
		}
		return $this->awayTeamId;
	}

	/**
	 * Return away team name
	 *
	 * @return String
	 */
	public function getAwayTeamName()
	{
		if(!isset($this->awayTeamName) || $this->awayTeamName === null)
		{
			$this->awayTeamName = $this->getXml()->getElementsByTagName('AwayTeamName')->item(0)->nodeValue;
		}
		return $this->awayTeamName;
	}

	/**
	 * Return match date
	 *
	 * @param String $format (php date() function format)
	 * @return String
	 */
	public function getDate($format = null)
	{
		if(!isset($this->date) || $this->date === null)
		{
			$this->date = $this->getXml()->getElementsByTagName('MatchDate')->item(0)->nodeValue;
			if($format !== null)
			{
				$this->date = HTFunction::convertDate($this->date, $format);
			}
		}
		return $this->date;
	}

	/**
	 * Return home goals
	 *
	 * @return Integer
	 */
	public function getHomeGoals()
	{
		if(!isset($this->homeGoals) || $this->homeGoals === null)
		{
			$this->homeGoals = $this->getXml()->getElementsByTagName('HomeGoals')->item(0)->nodeValue;
		}
		return $this->homeGoals;
	}

	/**
	 * Return away goals
	 *
	 * @return Integer
	 */
	public function getAwayGoals()
	{
		if(!isset($this->awayGoals) || $this->awayGoals === null)
		{
			$this->awayGoals = $this->getXml()->getElementsByTagName('AwayGoals')->item(0)->nodeValue;
		}
		return $this->awayGoals;
	}

	/**
	 * Return number event
	 *
	 * @return Integer
	 */
	public function getEventNumber()
	{
		if(!isset($this->eventNumber) || $this->eventNumber === null)
		{
			$xpath = new DOMXPath($this->getXml());
			$nodeList = $xpath->query('//Event');
			$this->eventNumber = $nodeList->length;
		}
		return $this->eventNumber;
	}

	/**
	 * Return HTLiveMatchEvent object
	 *
	 * @param Integer $number
	 * @return HTLiveMatchEvent
	 */
	public function getEvent($number)
	{
		$number = round($number);
		if($number > 0 && $number <= $this->getEventNumber())
		{
			--$number;
			if(!isset($this->events[$number]) || $this->events[$number] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//Event');
				$event = new DOMDocument('1.0', 'UTF-8');
				$event->appendChild($event->importNode($nodeList->item($number), true));
				$this->events[$number] = new HTLiveMatchEvent($event);
			}
			return $this->events[$number];
		}
		return null;
	}

	/**
	 * Return HTLiveMatchEvent object
	 *
	 * @param Integer $index
	 * @return HTLiveMatchEvent
	 */
	public function getEventByOrder($number)
	{
		$number = round($number);
		if($number > 0 && $number <= $this->getEventNumber())
		{
			if(!isset($this->eventsIndex[$number]) || $this->eventsIndex[$number] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//Event[@Index='.$number.']');
				$event = new DOMDocument('1.0', 'UTF-8');
				$event->appendChild($event->importNode($nodeList->item(0), true));
				$this->eventsIndex[$number] = new HTLiveMatchEvent($event);
			}
			return $this->eventsIndex[$number];
		}
		return null;
	}
}
class HTLiveMatchEvent extends HTXml
{
	private $minute = null;
	private $subjectPlayerId = null;
	private $subjectTeamId = null;
	private $objectPlayerId = null;
	private $key = null;
	private $text = null;
	private $moreInfos = null;

	/**
	 * @param DOMDocument $xml
	 */
	public function __construct($xml)
	{
		$this->xmlText = $xml->saveXML();
		$this->xml = $xml;
	}

	/**
	 * Return event minute
	 *
	 * @return Integer
	 */
	public function getMinute()
	{
		if(!isset($this->minute) || $this->minute === null)
		{
			$this->minute = $this->getXml()->getElementsByTagName('Minute')->item(0)->nodeValue;
		}
		return $this->minute;
	}

	/**
	 * Return event subject player id
	 *
	 * @return Integer
	 */
	public function getSubjectPlayerId()
	{
		if(!isset($this->subjectPlayerId) || $this->subjectPlayerId === null)
		{
			$this->subjectPlayerId = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerId;
	}

	/**
	 * Return event subject team id
	 *
	 * @return Integer
	 */
	public function getSubjectTeamId()
	{
		if(!isset($this->subjectTeamId) || $this->subjectTeamId === null)
		{
			$this->subjectTeamId = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamId;
	}

	/**
	 * Return event object player id
	 *
	 * @return Integer
	 */
	public function getObjectPlayerId()
	{
		if(!isset($this->objectPlayerId) || $this->objectPlayerId === null)
		{
			$this->objectPlayerId = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerId;
	}

	/**
	 * Return event key
	 *
	 * @return String
	 */
	public function getKey()
	{
		if(!isset($this->key) || $this->key === null)
		{
			$this->key = $this->getXml()->getElementsByTagName('EventKey')->item(0)->nodeValue;
		}
		return $this->key;
	}

	/**
	 * Return event text
	 *
	 * @param String $playerUrlReplacement (given url is concat with : PlayerID=xxxxxxx )
	 * @return String
	 */
	public function getText($playerUrlReplacement = null)
	{
		if(!isset($this->text) || $this->text === null)
		{
			$this->text = $this->getXml()->getElementsByTagName('EventText')->item(0)->nodeValue;
			if($playerUrlReplacement !== null)
			{
				$this->text = HTFunction::updatePlayerUrl($this->text, $playerUrlReplacement);
			}
			else
			{
				$this->text = HTFunction::updatePlayerUrl($this->text, CHPPConnection::SERVER_URL . HTFunction::PLAYERURL);
			}
		}
		return $this->text;
	}

	/**
	 * Event has more infos ?
	 *
	 * @return Boolean
	 */
	public function hasMoreInfos()
	{
		if(!isset($this->moreInfos) || $this->moreInfos === null)
		{
			$this->moreInfos = class_exists($this->getEventClassName());
		}
		return $this->moreInfos;
	}

	/**
	 * @return String
	 */
	private function getEventClassName()
	{
		return 'HTEvent'.$this->getMainKey();
	}

	/**
	 * @return Integer
	 */
	public function getMainKey()
	{
		$key = explode('_', $this->getKey());
		return $key[0];
	}

	/**
	 * Return HTEvent* object, depends on event ID
	 *
	 * @return HTEvent
	 */
	public function getInfos()
	{
		if($this->hasMoreInfos())
		{
			$class = $this->getEventClassName();
			return new $class($this->getXml());
		}
		return null;
	}
}
class HTNationalTeams extends HTGlobal
{
	private $leagueOfficeTypeID = null;
	private $numberNationalTeams = null;
	private $nationalTeams = null;
	private $nationalTeamsByName = null;
	private $cup = null;

	/**
	 * Return league office type id (2 = A, 4 = U20)
	 *
	 * @return Integer
	 */
	public function getLeagueOfficeTypeId()
	{
		if(!isset($this->leagueOfficeTypeID) || $this->leagueOfficeTypeID === null)
		{
			$this->leagueOfficeTypeID = $this->getXml()->getElementsByTagName('LeagueOfficeTypeID')->item(0)->nodeValue;
		}
		return $this->leagueOfficeTypeID;
	}

	/**
	 * Return number of national teams
	 *
	 * @return Integer
	 */
	public function getNumberNationalTeams()
	{
		if(!isset($this->numberNationalTeams) || $this->numberNationalTeams === null)
		{
			$this->numberNationalTeams = $this->getXml()->getElementsByTagName('NationalTeam')->length;
		}
		return $this->numberNationalTeams;
	}

	/**
	 * Return HTNationalTeamsTeam object
	 *
	 * @param Integer $index
	 * @return HTNationalTeamsTeam
	 */
	public function getTeam($index)
	{
		$index = round($index);
		if($index > 0 && $index <= $this->getNumberNationalTeams())
		{
			--$index;
			if(!isset($this->nationalTeams[$index]) || $this->nationalTeams[$index] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//NationalTeam');
				$team = new DOMDocument('1.0', 'UTF-8');
				$team->appendChild($team->importNode($nodeList->item($index), true));
				$this->nationalTeams[$index] = new HTNationalTeamsTeam($team);
			}
			return $this->nationalTeams[$index];
		}
		return null;
	}

	/**
	 * Return HTNationalTeamsTeam object
	 *
	 * @param String $name
	 * @return HTNationalTeamsTeam
	 */
	public function getTeamByLocalName($name)
	{
		if(!isset($this->nationalTeamsByName[$name]) || $this->nationalTeamsByName[$name] === null)
		{
			$xpath = new DOMXPath($this->getXml());
			$nodeList = $xpath->query('//NationalTeamName[.="'.$name.'"]');
			$team = new DOMDocument('1.0', 'UTF-8');
			$team->appendChild($team->importNode($nodeList->item(0)->parentNode, true));
			$this->nationalTeamsByName[$name] = new HTNationalTeamsTeam($team);
		}
		return $this->nationalTeamsByName[$name];
	}

	/**
	 * Return HTNationalTeamsCup object
	 *
	 * @return HTNationalTeamsCup
	 */
	public function getWorldCup()
	{
		if(!isset($this->cup) || $this->cup === null)
		{
			$xpath = new DOMXPath($this->getXml());
			$nodeList = $xpath->query('//Cup');
			$cup = new DOMDocument('1.0', 'UTF-8');
			$cup->appendChild($cup->importNode($nodeList->item(0), true));
			$this->cup = new HTNationalTeamsCup($cup);
		}
		return $this->cup;
	}
}
class HTNationalTeamsTeam extends HTXml
{
	private $id = null;
	private $name = null;
	private $dress = null;
	private $ratingScore = null;

	/**
	 * @param DOMDocument $xml
	 */
	public function __construct($xml)
	{
		$this->xmlText = $xml->saveXML();
		$this->xml = $xml;
	}

	/**
	 * Return national team id
	 *
	 * @return Integer
	 */
	public function getId()
	{
		if(!isset($this->id) || $this->id === null)
		{
			$this->id = $this->getXml()->getElementsByTagName('NationalTeamID')->item(0)->nodeValue;
		}
		return $this->id;
	}

	/**
	 * Return national team name
	 *
	 * @return String
	 */
	public function getName()
	{
		if(!isset($this->name) || $this->name === null)
		{
			$this->name = $this->getXml()->getElementsByTagName('NationalTeamName')->item(0)->nodeValue;
		}
		return $this->name;
	}

	/**
	 * Return national team dress code
	 *
	 * @return String
	 */
	public function getDressCode()
	{
		if(!isset($this->dress) || $this->dress === null)
		{
			$this->dress = $this->getXml()->getElementsByTagName('Dress')->item(0)->nodeValue;
		}
		return $this->dress;
	}

	/**
	 * Return national team score rating
	 *
	 * @return Integer
	 */
	public function getRatingScore()
	{
		if(!isset($this->ratingScore) || $this->ratingScore === null)
		{
			$this->ratingScore = $this->getXml()->getElementsByTagName('RatingScore')->item(0)->nodeValue;
		}
		return $this->ratingScore;
	}
}
class HTNationalTeamsCup extends HTXml
{
	private $id = null;
	private $teamsListAvailable = null;
	private $numberTeams = null;
	private $teams = null;
	private $teamsLocalName = null;

	/**
	 * @param DOMDocument $xml
	 */
	public function __construct($xml)
	{
		$this->xmlText = $xml->saveXML();
		$this->xml = $xml;
	}

	/**
	 * Return current worldcup id
	 *
	 * @return Integer
	 */
	public function getId()
	{
		if(!isset($this->id) || $this->id === null)
		{
			$this->id = $this->getXml()->getElementsByTagName('CupID')->item(0)->nodeValue;
		}
		return $this->id;
	}

	/**
	 * Is list of teams still in cup available ?
	 *
	 * @return Boolean
	 */
	public function isTeamsListAvailable()
	{
		if(!isset($this->teamsListAvailable) || $this->teamsListAvailable === null)
		{
			$this->teamsListAvailable = strtolower($this->getXml()->getElementsByTagName('CupTeams')->item(0)->getAttribute('Available')) == 'true';
		}
		return $this->teamsListAvailable;
	}

	/**
	 * Return number of teams still in world cup
	 *
	 * @return Integer
	 */
	public function getNumberTeamsStillInCup()
	{
		if(!isset($this->numberTeams) || $this->numberTeams === null)
		{
			$this->numberTeams = $this->getXml()->getElementsByTagName('CupTeam')->length;
		}
		return $this->numberTeams;
	}

	/**
	 * Return HTNationalTeamsCupTeam object
	 *
	 * @param Integer $index
	 * @return HTNationalTeamsCupTeam
	 */
	public function getTeam($index)
	{
		$index = round($index);
		if($index > 0 && $index <= $this->getNumberTeamsStillInCup())
		{
			if(!isset($this->teams[$index]) || $this->teams[$index] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//CupTeam');
				$team = new DOMDocument('1.0', 'UTF-8');
				$team->appendChild($team->importNode($nodeList->item($index), true));
				$this->teams[$index] = new HTNationalTeamsCupTeam($team);
			}
			return $this->teams[$index];
		}
		return null;
	}

	/**
	 * Return HTNationalTeamsCupTeam object
	 *
	 * @param String $name
	 * @return HTNationalTeamsCupTeam
	 */
	public function getTeamByLocalName($name)
	{
		if(!isset($this->teamsLocalName[$name]) || $this->teamsLocalName[$name] === null)
		{
			$xpath = new DOMXPath($this->getXml());
			$nodeList = $xpath->query('//CupNationalTeamName[.="'.$name.'"]');
			$team = new DOMDocument('1.0', 'UTF-8');
			$team->appendChild($team->importNode($nodeList->item(0)->parentNode, true));
			$this->teamsLocalName[$name] = new HTNationalTeamsCupTeam($team);
		}
		return $this->teamsLocalName[$name];
	}
}
class HTNationalTeamsCupTeam extends HTXml
{
	private $id = null;
	private $name = null;

	/**
	 * @param DOMDocument $xml
	 */
	public function __construct($xml)
	{
		$this->xmlText = $xml->saveXML();
		$this->xml = $xml;
	}

	/**
	 * Return team id
	 *
	 * @return Integer
	 */
	public function getId()
	{
		if(!isset($this->id) || $this->id === null)
		{
			$this->id = $this->getXml()->getElementsByTagName('CupNationalTeamID')->item(0)->nodeValue;
		}
		return $this->id;
	}

	/**
	 * Return team name
	 *
	 * @return String
	 */
	public function getName()
	{
		if(!isset($this->name) || $this->name === null)
		{
			$this->name = $this->getXml()->getElementsByTagName('CupNationalTeamName')->item(0)->nodeValue;
		}
		return $this->name;
	}
}
class HTNationalTeamDetail extends HTCommonTeam
{
	private $coachUserId = null;
	private $coachUserName = null;
	private $shortTeamName = null;
	private $leagueId = null;
	private $leagueName = null;
	private $trainerId = null;
	private $trainerName = null;
	private $homepage = null;
	private $dress = null;
	private $dressAlternate = null;
	private $experience433 = null;
	private $experience451 = null;
	private $experience352 = null;
	private $experience532 = null;
	private $experience343 = null;
	private $experience541 = null;
	private $morale = null;
	private $selfConfidence = null;
	private $supportersPopularity = null;
	private $ratingScore = null;
	private $fanClubSize = null;
	private $rank = null;
	private $isPlaying = null;

	/**
	 * Return coach user id
	 *
	 * @return Integer
	 */
	public function getCoachUserId()
	{
		if(!isset($this->coachUserId) || $this->coachUserId === null)
		{
			$this->coachUserId = $this->getXml()->getElementsByTagName('NationalCoachUserID')->item(0)->nodeValue;
		}
		return $this->coachUserId;
	}

	/**
	 * Return coach user name
	 *
	 * @return String
	 */
	public function getCoachUserName()
	{
		if(!isset($this->coachUserName) || $this->coachUserName === null)
		{
			$this->coachUserName = $this->getXml()->getElementsByTagName('NationalCoachLoginname')->item(0)->nodeValue;
		}
		return $this->coachUserName;
	}

	/**
	 * Return short team name
	 *
	 * @return String
	 */
	public function getShortTeamName()
	{
		if(!isset($this->shortTeamName) || $this->shortTeamName === null)
		{
			$this->shortTeamName = $this->getXml()->getElementsByTagName('ShortTeamName')->item(0)->nodeValue;
		}
		return $this->shortTeamName;
	}

	/**
	 * Return league id
	 *
	 * @return Integer
	 */
	public function getLeagueId()
	{
		if(!isset($this->leagueId) || $this->leagueId === null)
		{
			$this->leagueId = $this->getXml()->getElementsByTagName('LeagueID')->item(0)->nodeValue;
		}
		return $this->leagueId;
	}

	/**
	 * Return league name
	 *
	 * @return String
	 */
	public function getLeagueName()
	{
		if(!isset($this->leagueName) || $this->leagueName === null)
		{
			$this->leagueName = $this->getXml()->getElementsByTagName('LeagueName')->item(0)->nodeValue;
		}
		return $this->leagueName;
	}

	/**
	 * Return trainer player id
	 *
	 * @return Integer
	 */
	public function getTrainerId()
	{
		if(!isset($this->trainerId) || $this->trainerId === null)
		{
			$this->trainerId = $this->getXml()->getElementsByTagName('PlayerID')->item(0)->nodeValue;
		}
		return $this->trainerId;
	}

	/**
	 * Return trainer player name
	 *
	 * @return String
	 */
	public function getTrainerName()
	{
		if(!isset($this->trainerName) || $this->trainerName === null)
		{
			$this->trainerName = $this->getXml()->getElementsByTagName('PlayerName')->item(0)->nodeValue;
		}
		return $this->trainerName;
	}

	/**
	 * Return home page url
	 *
	 * @return String
	 */
	public function getHomepageUrl()
	{
		if(!isset($this->homepage) || $this->homepage === null)
		{
			$this->homepage = $this->getXml()->getElementsByTagName('HomePage')->item(0)->nodeValue;
		}
		return $this->homepage;
	}

	/**
	 * Return dress code
	 *
	 * @return String
	 */
	public function getDressCode()
	{
		if(!isset($this->dress) || $this->dress === null)
		{
			$this->dress = $this->getXml()->getElementsByTagName('Dress')->item(0)->nodeValue;
		}
		return $this->dress;
	}

	/**
	 * Return dress alternate code
	 *
	 * @return String
	 */
	public function getDressAlternateCode()
	{
		if(!isset($this->dressAlternate) || $this->dressAlternate === null)
		{
			$this->dressAlternate = $this->getXml()->getElementsByTagName('DressAlternate')->item(0)->nodeValue;
		}
		return $this->dressAlternate;
	}

	/**
	 * Return 433 experience level
	 *
	 * @return Integer
	 */
	public function get433Experience()
	{
		if(!isset($this->experience433) || $this->experience433 === null)
		{
			$this->experience433 = $this->getXml()->getElementsByTagName('Experience433')->item(0)->nodeValue;
		}
		return $this->experience433;
	}

	/**
	 * Return 451 experience level
	 *
	 * @return Integer
	 */
	public function get451Experience()
	{
		if(!isset($this->experience451) || $this->experience451 === null)
		{
			$this->experience451 = $this->getXml()->getElementsByTagName('Experience451')->item(0)->nodeValue;
		}
		return $this->experience451;
	}

	/**
	 * Return 352 experience level
	 *
	 * @return Integer
	 */
	public function get352Experience()
	{
		if(!isset($this->experience352) || $this->experience352 === null)
		{
			$this->experience352 = $this->getXml()->getElementsByTagName('Experience352')->item(0)->nodeValue;
		}
		return $this->experience352;
	}

	/**
	 * Return 532 experience level
	 *
	 * @return Integer
	 */
	public function get532Experience()
	{
		if(!isset($this->experience532) || $this->experience532 === null)
		{
			$this->experience532 = $this->getXml()->getElementsByTagName('Experience532')->item(0)->nodeValue;
		}
		return $this->experience532;
	}

	/**
	 * Return 343 experience level
	 *
	 * @return Integer
	 */
	public function get343Experience()
	{
		if(!isset($this->experience343) || $this->experience343 === null)
		{
			$this->experience343 = $this->getXml()->getElementsByTagName('Experience343')->item(0)->nodeValue;
		}
		return $this->experience343;
	}

	/**
	 * Return 451 experience level
	 *
	 * @return Integer
	 */
	public function get541Experience()
	{
		if(!isset($this->experience541) || $this->experience541 === null)
		{
			$this->experience541 = $this->getXml()->getElementsByTagName('Experience541')->item(0)->nodeValue;
		}
		return $this->experience541;
	}

	/**
	 * Return team spirit level
	 *
	 * @return Integer
	 */
	public function getTeamSpirit()
	{
		if(!isset($this->morale) || $this->morale === null)
		{
			$this->morale = $this->getXml()->getElementsByTagName('Morale')->item(0)->nodeValue;
		}
		return $this->morale;
	}

	/**
	 * Return self confidence level
	 *
	 * @return Integer
	 */
	public function getSelfConfidence()
	{
		if(!isset($this->selfConfidence) || $this->selfConfidence === null)
		{
			$this->selfConfidence = $this->getXml()->getElementsByTagName('SelfConfidence')->item(0)->nodeValue;
		}
		return $this->selfConfidence;
	}

	/**
	 * Return self confidence level
	 *
	 * @return Integer
	 */
	public function getSupportersPopularity()
	{
		if(!isset($this->supportersPopularity) || $this->supportersPopularity === null)
		{
			$this->supportersPopularity = $this->getXml()->getElementsByTagName('SupportersPopularity')->item(0)->nodeValue;
		}
		return $this->supportersPopularity;
	}

	/**
	 * Return rating score
	 *
	 * @return Integer
	 */
	public function getRatingScore()
	{
		if(!isset($this->ratingScore) || $this->ratingScore === null)
		{
			$this->ratingScore = $this->getXml()->getElementsByTagName('RatingScore')->item(0)->nodeValue;
		}
		return $this->ratingScore;
	}

	/**
	 * Return fan club size
	 *
	 * @return Integer
	 */
	public function getFanClubSize()
	{
		if(!isset($this->fanClubSize) || $this->fanClubSize === null)
		{
			$this->fanClubSize = $this->getXml()->getElementsByTagName('FanClubSize')->item(0)->nodeValue;
		}
		return $this->fanClubSize;
	}

	/**
	 * Return team rank
	 *
	 * @return Integer
	 */
	public function getRank()
	{
		if(!isset($this->rank) || $this->rank === null)
		{
			$this->rank = $this->getXml()->getElementsByTagName('Rank')->item(0)->nodeValue;
		}
		return $this->rank;
	}

	/**
	 * Return if team is playing a match
	 *
	 * @return Boolean
	 */
	public function isPlayingMatch()
	{
		if(!isset($this->isPlaying) || $this->isPlaying === null)
		{
			$this->isPlaying = strtolower($this->getXml()->getElementsByTagName('IsPlayingMatch')->item(0)->nodeValue) == "true";
		}
		return $this->isPlaying;
	}
}
class HTNationalPlayers extends HTCommonSubscriber
{
	private $numberPlayers = null;
	private $players = null;

	/**
	 * Return number of players in team
	 *
	 * @return Integer
	 */
	public function getNumberPlayers()
	{
		if(!isset($this->numberPlayers) || $this->numberPlayers === null)
		{
			$this->numberPlayers = $this->getXml()->getElementsByTagName('Player')->length;
		}
		return $this->numberPlayers;
	}

	/**
	 * Return HTNationalPlayer object
	 *
	 * @param Integer $index
	 * @return HTNationalPlayer
	 */
	public function getPlayer($index)
	{
		$index = round($index);
		if($index > 0 && $index <= $this->getNumberPlayers())
		{
			--$index;
			if(!isset($this->players[$index]) || $this->players[$index] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//Player');
				$player = new DOMDocument('1.0', 'UTF-8');
				$player->appendChild($player->importNode($nodeList->item($index), true));
				$this->players[$index] = new HTNationalPlayer($player);
			}
			return $this->players[$index];
		}
		return null;
	}
}
class HTNationalPlayer extends HTXml
{
	private $id = null;
	private $name = null;

	/**
	 * @param DOMDocument $xml
	 */
	public function __construct($xml)
	{
		$this->xmlText = $xml->saveXML();
		$this->xml = $xml;
	}

	/**
	 * Return player id
	 *
	 * @return Integer
	 */
	public function getId()
	{
		if(!isset($this->id) || $this->id === null)
		{
			$this->id = $this->getXml()->getElementsByTagName('PlayerID')->item(0)->nodeValue;
		}
		return $this->id;
	}

	/**
	 * Return player name
	 *
	 * @return String
	 */
	public function getName()
	{
		if(!isset($this->name) || $this->name === null)
		{
			$this->name = $this->getXml()->getElementsByTagName('PlayerName')->item(0)->nodeValue;
		}
		return $this->name;
	}
}
class HTWorldCupGroup extends HTGlobal
{
	private $cupId = null;
	private $season = null;
	private $matchRound = null;
	private $numberTeams = null;
	private $teams = null;
	private $teamsByName = null;
	private $numberRounds = null;
	private $rounds = null;

	/**
	 * Return world cup id
	 *
	 * @return Integer
	 */
	public function getId()
	{
		if(!isset($this->cupId) || $this->cupId === null)
		{
			$this->cupId = $this->getXml()->getElementsByTagName('CupID')->item(0)->nodeValue;
		}
		return $this->cupId;
	}

	/**
	 * Return world cup season
	 *
	 * @return Integer
	 */
	public function getSeason()
	{
		if(!isset($this->season) || $this->season === null)
		{
			$this->season = $this->getXml()->getElementsByTagName('Season')->item(0)->nodeValue;
		}
		return $this->season;
	}

	/**
	 * Return world cup match round
	 *
	 * @return Integer
	 */
	public function getMatchRound()
	{
		if(!isset($this->matchRound) || $this->matchRound === null)
		{
			$this->matchRound = $this->getXml()->getElementsByTagName('MatchRound')->item(0)->nodeValue;
		}
		return $this->matchRound;
	}

	/**
	 * Return number of teams
	 *
	 * @return Integer
	 */
	public function getNumberTeams()
	{
		if(!isset($this->numberTeams) || $this->numberTeams === null)
		{
			$this->numberTeams = $this->getXml()->getElementsByTagName('CupSeriesScores')->item(0)->getAttribute('Count');
		}
		return $this->numberTeams;
	}

	/**
	 * Return HTWorldCupGroupTeam object
	 *
	 * @param Integer $index
	 * @return HTWorldCupGroupTeam
	 */
	public function getTeam($index)
	{
		$index = round($index);
		if($index > 0 && $index <= $this->getNumberTeams())
		{
			--$index;
			if(!isset($this->teams[$index]) || $this->teams[$index] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//Team');
				$team = new DOMDocument('1.0', 'UTF-8');
				$team->appendChild($team->importNode($nodeList->item($index), true));
				$this->teams[$index] = new HTWorldCupGroupTeam($team);
			}
			return $this->teams[$index];
		}
		return null;
	}

	/**
	 * Return HTWorldCupGroupTeam object
	 *
	 * @param Integer $index
	 * @return HTWorldCupGroupTeam
	 */
	public function getTeamByLocalName($name)
	{
		if(!isset($this->teamsByName[$name]) || $this->teamsByName[$name] === null)
		{
			$xpath = new DOMXPath($this->getXml());
			$nodeList = $xpath->query('//TeamName[.="'.$name.'"]');
			$team = new DOMDocument('1.0', 'UTF-8');
			$team->appendChild($team->importNode($nodeList->item(0)->parentNode, true));
			$this->teamsByName[$name] = new HTWorldCupGroupTeam($team);
		}
		return $this->teamsByName[$name];
	}

	/**
	 * Return number of rounds
	 *
	 * @return Integer
	 */
	public function getNumberRounds()
	{
		if(!isset($this->numberRounds) || $this->numberRounds === null)
		{
			$this->numberRounds = $this->getXml()->getElementsByTagName('Round')->length;
		}
		return $this->numberRounds;
	}

	/**
	 * Return HTWorldCupGroupRound object
	 *
	 * @param Integer $index
	 * @return HTWorldCupGroupRound
	 */
	public function getRound($index)
	{
		$index = round($index);
		if($index > 0 && $index <= $this->getNumberRounds())
		{
			--$index;
			if(!isset($this->rounds[$index]) || $this->rounds[$index] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//Round');
				$round = new DOMDocument('1.0', 'UTF-8');
				$round->appendChild($round->importNode($nodeList->item($index), true));
				$this->rounds[$index] = new HTWorldCupGroupRound($round);
			}
			return $this->rounds[$index];
		}
		return null;
	}
}
class HTWorldCupGroupTeam extends HTXml
{
	private $id = null;
	private $name = null;
	private $place = null;
	private $cupSeriesUnitId = null;
	private $cupSeriesUnitName = null;
	private $matchesPlayed = null;
	private $goalsFor = null;
	private $goalsAgainst = null;
	private $points = null;

	/**
	 * @param DOMDocument $xml
	 */
	public function __construct($xml)
	{
		$this->xmlText = $xml->saveXML();
		$this->xml = $xml;
	}

	/**
	 * Return team id
	 *
	 * @return Integer
	 */
	public function getId()
	{
		if(!isset($this->id) || $this->id === null)
		{
			$this->id = $this->getXml()->getElementsByTagName('TeamID')->item(0)->nodeValue;
		}
		return $this->id;
	}

	/**
	 * Return team name
	 *
	 * @return String
	 */
	public function getName()
	{
		if(!isset($this->name) || $this->name === null)
		{
			$this->name = $this->getXml()->getElementsByTagName('TeamName')->item(0)->nodeValue;
		}
		return $this->name;
	}

	/**
	 * Return team place
	 *
	 * @return Integer
	 */
	public function getPlace()
	{
		if(!isset($this->place) || $this->place === null)
		{
			$this->place = $this->getXml()->getElementsByTagName('Place')->item(0)->nodeValue;
		}
		return $this->place;
	}

	/**
	 * Return cup series unit id
	 *
	 * @return Integer
	 */
	public function getCupSeriesUnitId()
	{
		if(!isset($this->cupSeriesUnitId) || $this->cupSeriesUnitId === null)
		{
			$this->cupSeriesUnitId = $this->getXml()->getElementsByTagName('CupSeriesUnitID')->item(0)->nodeValue;
		}
		return $this->cupSeriesUnitId;
	}

	/**
	 * Return cup series unit name
	 *
	 * @return String
	 */
	public function getCupSeriesUnitName()
	{
		if(!isset($this->cupSeriesUnitName) || $this->cupSeriesUnitName === null)
		{
			$this->cupSeriesUnitName = $this->getXml()->getElementsByTagName('CupSeriesUnitName')->item(0)->nodeValue;
		}
		return $this->cupSeriesUnitName;
	}

	/**
	 * Return number of played matches
	 *
	 * @return Integer
	 */
	public function getMatchesPlayedNumber()
	{
		if(!isset($this->matchesPlayed) || $this->matchesPlayed === null)
		{
			$this->matchesPlayed = $this->getXml()->getElementsByTagName('MatchesPlayed')->item(0)->nodeValue;
		}
		return $this->matchesPlayed;
	}

	/**
	 * Return number of goal for team
	 *
	 * @return Integer
	 */
	public function getGoalsFor()
	{
		if(!isset($this->goalsFor) || $this->goalsFor === null)
		{
			$this->goalsFor = $this->getXml()->getElementsByTagName('GoalsFor')->item(0)->nodeValue;
		}
		return $this->goalsFor;
	}

	/**
	 * Return number of goal against team
	 *
	 * @return Integer
	 */
	public function getGoalsAgainst()
	{
		if(!isset($this->goalsAgainst) || $this->goalsAgainst === null)
		{
			$this->goalsAgainst = $this->getXml()->getElementsByTagName('GoalsAgainst')->item(0)->nodeValue;
		}
		return $this->goalsAgainst;
	}

	/**
	 * Return points number
	 *
	 * @return Integer
	 */
	public function getPoints()
	{
		if(!isset($this->points) || $this->points === null)
		{
			$this->points = $this->getXml()->getElementsByTagName('Points')->item(0)->nodeValue;
		}
		return $this->points;
	}
}
class HTWorldCupGroupRound extends HTXml
{
	private $matchRound = null;
	private $date = null;

	/**
	 * @param DOMDocument $xml
	 */
	public function __construct($xml)
	{
		$this->xmlText = $xml->saveXML();
		$this->xml = $xml;
	}

	/**
	 * Return match round
	 *
	 * @return Integer
	 */
	public function getMatchRound()
	{
		if(!isset($this->matchRound) || $this->matchRound === null)
		{
			$this->matchRound = $this->getXml()->getElementsByTagName('MatchRound')->item(0)->nodeValue;
		}
		return $this->matchRound;
	}

	/**
	 * Return round date
	 *
	 * @param String $format (php date() function format)
	 * @return String
	 */
	public function getDate($format = null)
	{
		if(!isset($this->date) || $this->date === null)
		{
			$this->date = $this->getXml()->getElementsByTagName('StartDate')->item(0)->nodeValue;
			if($format !== null)
			{
				$this->date = HTFunction::convertDate($this->date, $format);
			}
		}
		return $this->date;
	}
}
class HTAlliances extends HTGlobal
{
	private $numberAlliances = null;
	private $alliances = null;
	private $pageIndex = null;
	private $pages = null;

	/**
	 * Return number of alliances
	 *
	 * @return Integer
	 */
	public function getNumberAlliances()
	{
		if(!isset($this->numberAlliances) || $this->numberAlliances === null)
		{
			$this->numberAlliances = $this->getXml()->getElementsByTagName('Alliance')->length;
		}
		return $this->numberAlliances;
	}

	/**
	 * Return HTAlliance object
	 *
	 * @param Integer $index
	 * @return HTAlliance
	 */
	public function getAlliance($index)
	{
		$index = round($index);
		if($index > 0 && $index <= $this->getNumberAlliances())
		{
			--$index;
			if(!isset($this->alliances[$index]) || $this->alliances[$index] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//Alliance');
				$alliance = new DOMDocument('1.0', 'UTF-8');
				$alliance->appendChild($alliance->importNode($nodeList->item($index), true));
				$this->alliances[$index] = new HTAlliance($alliance);
			}
			return $this->alliances[$index];
		}
		return null;
	}

	/**
	 * Return page index
	 *
	 * @return Integer
	 */
	public function getPageIndex()
	{
		if(!isset($this->pageIndex) || $this->pageIndex === null)
		{
			$this->pageIndex = $this->getXml()->getElementsByTagName('PageIndex')->item(0)->nodeValue;
		}
		return $this->pageIndex;
	}

	/**
	 * Return page number
	 *
	 * @return Integer
	 */
	public function getPageNumber()
	{
		if(!isset($this->pages) || $this->pages === null)
		{
			$this->pages = $this->getXml()->getElementsByTagName('Pages')->item(0)->nodeValue;
		}
		return $this->pages;
	}
}
class HTAlliance extends HTXml
{
	private $id = null;
	private $name = null;
	private $description = null;

	/**
	 * @param DOMDocument $xml
	 */
	public function __construct($xml)
	{
		$this->xmlText = $xml->saveXML();
		$this->xml = $xml;
	}

	/**
	 * Return alliance id
	 *
	 * @return Integer
	 */
	public function getId()
	{
		if(!isset($this->id) || $this->id === null)
		{
			$this->id = $this->getXml()->getElementsByTagName('AllianceID')->item(0)->nodeValue;
		}
		return $this->id;
	}

	/**
	 * Return alliance name
	 *
	 * @return String
	 */
	public function getName()
	{
		if(!isset($this->name) || $this->name === null)
		{
			$this->name = $this->getXml()->getElementsByTagName('AllianceName')->item(0)->nodeValue;
		}
		return $this->name;
	}

	/**
	 * Return alliance description
	 *
	 * @return String
	 */
	public function getDescription()
	{
		if(!isset($this->description) || $this->description === null)
		{
			$this->description = $this->getXml()->getElementsByTagName('AllianceDescription')->item(0)->nodeValue;
		}
		return $this->description;
	}
}
class HTArena extends HTCommonTeam
{
	private $id = null;
	private $name = null;
	private $leagueId = null;
	private $leagueName = null;
	private $regionId = null;
	private $regionName = null;
	private $currentCapacity = null;
	private $futureCapacity = null;
	private $futureCapacityAvailable = null;

	/**
	 * Return arena id
	 *
	 * @return Integer
	 */
	public function getId()
	{
		if(!isset($this->id) || $this->id === null)
		{
			$this->id = $this->getXml()->getElementsByTagName('ArenaID')->item(0)->nodeValue;
		}
		return $this->id;
	}

	/**
	 * Return arena name
	 *
	 * @return String
	 */
	public function getName()
	{
		if(!isset($this->name) || $this->name === null)
		{
			$this->name = $this->getXml()->getElementsByTagName('ArenaName')->item(0)->nodeValue;
		}
		return $this->name;
	}

	/**
	 * Return arena league id
	 *
	 * @return Integer
	 */
	public function getLeagueId()
	{
		if(!isset($this->leagueId) || $this->leagueId === null)
		{
			$this->leagueId = $this->getXml()->getElementsByTagName('LeagueID')->item(0)->nodeValue;
		}
		return $this->leagueId;
	}

	/**
	 * Return arena league name
	 *
	 * @return String
	 */
	public function getLeagueName()
	{
		if(!isset($this->leagueName) || $this->leagueName === null)
		{
			$this->leagueName = $this->getXml()->getElementsByTagName('LeagueName')->item(0)->nodeValue;
		}
		return $this->leagueName;
	}

	/**
	 * Return arena region id
	 *
	 * @return Integer
	 */
	public function getRegionId()
	{
		if(!isset($this->regionId) || $this->regionId === null)
		{
			$this->regionId = $this->getXml()->getElementsByTagName('RegionID')->item(0)->nodeValue;
		}
		return $this->regionId;
	}

	/**
	 * Return arena region name
	 *
	 * @return String
	 */
	public function getRegionName()
	{
		if(!isset($this->regionName) || $this->regionName === null)
		{
			$this->regionName = $this->getXml()->getElementsByTagName('RegionName')->item(0)->nodeValue;
		}
		return $this->regionName;
	}

	/**
	 * Return HTArenaCapacity object (current capacity)
	 *
	 * @return HTArenaCapacity
	 */
	public function getCurrentCapacity()
	{
		if(!isset($this->currentCapacity) || $this->currentCapacity === null)
		{
			$xpath = new DOMXPath($this->getXml());
			$nodeList = $xpath->query('//CurrentCapacity');
			$capacity = new DOMDocument('1.0', 'UTF-8');
			$capacity->appendChild($capacity->importNode($nodeList->item(0), true));
			$this->currentCapacity = new HTArenaCapacity($capacity);
		}
		return $this->currentCapacity;
	}

	/**
	 * Is future capacity available ?
	 *
	 * @return Boolean
	 */
	public function isFutureCapacityAvailable()
	{
		if(!isset($this->futureCapacityAvailable) || $this->futureCapacityAvailable === null)
		{
			$this->futureCapacityAvailable = strtolower($this->getXml()->getElementsByTagName('ExpandedCapacity')->item(0)->getAttribute('Available')) == 'true';
		}
		return $this->futureCapacityAvailable;
	}

	/**
	 * Return HTArenaCapacity object (expanded capacity)
	 *
	 * @return HTArenaCapacity
	 */
	public function getExpandedCapacity()
	{
		if($this->isFutureCapacityAvailable())
		{
			if(!isset($this->futureCapacity) || $this->futureCapacity === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//ExpandedCapacity');
				$capacity = new DOMDocument('1.0', 'UTF-8');
				$capacity->appendChild($capacity->importNode($nodeList->item(0), true));
				$this->futureCapacity = new HTArenaCapacity($capacity);
			}
			return $this->futureCapacity;
		}
		return null;
	}

	/**
	 * Return future arena capacity
	 *
	 * @return Integer
	 */
	public function getFutureCapacity()
	{
		if($this->isFutureCapacityAvailable())
		{
			return $this->getCurrentCapacity()+$this->getExpandedCapacity();
		}
		return $this->getCurrentCapacity();
	}
}
class HTArenaCapacity extends HTXml
{
	private $lastRebuildDateAvailable = null;
	private $lastRebuildDate = null;
	private $expansionDate = null;
	private $terraces = null;
	private $basic = null;
	private $roof = null;
	private $vip = null;
	private $total = null;
	const UNAVAILABLE = 'unavailable';

	/**
	 * @param DOMDocument $xml
	 */
	public function __construct($xml)
	{
		$this->xmlText = $xml->saveXML();
		$this->xml = $xml;
	}

	/**
	 * Is last rebuild arena date available ?
	 *
	 * @return Boolean
	 */
	public function isLastRebuildDateAvailable()
	{
		if(!isset($this->lastRebuildDateAvailable) || $this->lastRebuildDateAvailable === null)
		{
			$node = $this->getXml()->getElementsByTagName('RebuiltDate');
			if($node !== null && $node->length)
			{
				$this->lastRebuildDateAvailable = strtolower($this->getXml()->getElementsByTagName('RebuiltDate')->item(0)->getAttribute('Available')) == 'true';
			}
			else
			{
				$this->lastRebuildDateAvailable = self::UNAVAILABLE ;
			}
		}
		if($this->lastRebuildDateAvailable === self::UNAVAILABLE )
		{
			return false;
		}
		return $this->lastRebuildDateAvailable;
	}

	/**
	 * Return last rebuild arena date
	 *
	 * @param String $format (php date() function format)
	 * @return String
	 */
	public function getLastRebuildDate($format = null)
	{
		if($this->isLastRebuildDateAvailable())
		{
			if(!isset($this->lastRebuildDate) || $this->lastRebuildDate === null)
			{
				$this->lastRebuildDate = $this->getXml()->getElementsByTagName('RebuiltDate')->item(0)->nodeValue;
				if($format !== null)
				{
					$this->lastRebuildDate = HTFunction::convertDate($this->lastRebuildDate, $format);
				}
			}
			return $this->lastRebuildDate;
		}
		return null;
	}

	/**
	 * Return arena expansion date
	 *
	 * @param String $format (php date() function format)
	 * @return String
	 */
	public function getExpansionDate($format = null)
	{
		if(!isset($this->expansionDate) || $this->expansionDate === null)
		{
			$node = $this->getXml()->getElementsByTagName('ExpansionDate');
			if($node !== null && $node->length)
			{
				$this->expansionDate = $this->getXml()->getElementsByTagName('ExpansionDate')->item(0)->nodeValue;
				if($format !== null)
				{
					$this->expansionDate = HTFunction::convertDate($this->expansionDate, $format);
				}
			}
			else
			{
				$this->expansionDate = self::UNAVAILABLE;
			}
		}
		if($this->expansionDate === self::UNAVAILABLE )
		{
			return null;
		}
		return $this->expansionDate;
	}

	/**
	 * Return terraces seats number
	 *
	 * @return Integer
	 */
	public function getTerraces()
	{
		if(!isset($this->terraces) || $this->terraces === null)
		{
			$this->terraces = $this->getXml()->getElementsByTagName('Terraces')->item(0)->nodeValue;
		}
		return $this->terraces;
	}

	/**
	 * Return basic seats number
	 *
	 * @return Integer
	 */
	public function getBasic()
	{
		if(!isset($this->basic) || $this->basic === null)
		{
			$this->basic = $this->getXml()->getElementsByTagName('Basic')->item(0)->nodeValue;
		}
		return $this->basic;
	}

	/**
	 * Return roof seats number
	 *
	 * @return Integer
	 */
	public function getRoof()
	{
		if(!isset($this->roof) || $this->roof === null)
		{
			$this->roof = $this->getXml()->getElementsByTagName('Roof')->item(0)->nodeValue;
		}
		return $this->roof;
	}

	/**
	 * Return vip seats number
	 *
	 * @return Integer
	 */
	public function getVip()
	{
		if(!isset($this->vip) || $this->vip === null)
		{
			$this->vip = $this->getXml()->getElementsByTagName('VIP')->item(0)->nodeValue;
		}
		return $this->vip;
	}

	/**
	 * Return total seats number
	 *
	 * @return Integer
	 */
	public function getTotal()
	{
		if(!isset($this->total) || $this->total === null)
		{
			$node = $this->getXml()->getElementsByTagName('Total');
			if($node !== null && $node->length)
			{
				$this->total = $node->item(0)->nodeValue;
			}
			else
			{
				$this->total = $this->getTerraces()+$this->getBasic()+$this->getRoof()+$this->getVip();
			}
		}
		return $this->total;
	}
}
class HTMatchOrders extends HTGlobal
{
	private $dataAvailable = null;
	private $homeTeamId = null;
	private $homeTeamName = null;
	private $awayTeamId = null;
	private $awayTeamName = null;
	private $arenaId = null;
	private $arenaName = null;
	private $date = null;
	private $type = null;
	private $attitude = null;
	private $attitudeAvailable = null;
	private $tactic = null;
	private $players = null;
	private $playersByRole = null;
	private $playersNumber = null;
	private $ordersNumber = null;
	private $orders = null;
	private $ordersByPlayerId = null;
	private $ordersNumberByPlayerId = null;

	/**
	 * Are orders available ?
	 *
	 * @return Boolean
	 */
	public function isDataAvailable()
	{
		if(!isset($this->dataAvailable) || $this->dataAvailable === null)
		{
			$this->dataAvailable = strtolower($this->getXml()->getElementsByTagName('MatchData')->item(0)->getAttribute('Available')) == 'true';
		}
		return $this->dataAvailable;
	}

	/**
	 * Return home team id
	 *
	 * @return Integer
	 */
	public function getHomeTeamId()
	{
		if($this->isDataAvailable())
		{
			if(!isset($this->homeTeamId) || $this->homeTeamId === null)
			{
				$this->homeTeamId = $this->getXml()->getElementsByTagName('HomeTeamID')->item(0)->nodeValue;
			}
			return $this->homeTeamId;
		}
		return null;
	}

	/**
	 * Return home team name
	 *
	 * @return String
	 */
	public function getHomeTeamName()
	{
		if($this->isDataAvailable())
		{
			if(!isset($this->homeTeamName) || $this->homeTeamName === null)
			{
				$this->homeTeamName = $this->getXml()->getElementsByTagName('HomeTeamName')->item(0)->nodeValue;
			}
			return $this->homeTeamName;
		}
		return null;
	}

	/**
	 * Return away team id
	 *
	 * @return Integer
	 */
	public function getAwayTeamId()
	{
		if($this->isDataAvailable())
		{
			if(!isset($this->awayTeamId) || $this->awayTeamId === null)
			{
				$this->awayTeamId = $this->getXml()->getElementsByTagName('AwayTeamID')->item(0)->nodeValue;
			}
			return $this->awayTeamId;
		}
		return null;
	}

	/**
	 * Return away team name
	 *
	 * @return String
	 */
	public function getAwayTeamName()
	{
		if($this->isDataAvailable())
		{
			if(!isset($this->awayTeamName) || $this->awayTeamName === null)
			{
				$this->awayTeamName = $this->getXml()->getElementsByTagName('AwayTeamName')->item(0)->nodeValue;
			}
			return $this->awayTeamName;
		}
		return null;
	}

	/**
	 * Return arena id
	 *
	 * @return Integer
	 */
	public function getArenaId()
	{
		if($this->isDataAvailable())
		{
			if(!isset($this->arenaId) || $this->arenaId === null)
			{
				$this->arenaId = $this->getXml()->getElementsByTagName('ArenaID')->item(0)->nodeValue;
			}
			return $this->arenaId;
		}
		return null;
	}

	/**
	 * Return arena name
	 *
	 * @return String
	 */
	public function getArenaName()
	{
		if($this->isDataAvailable())
		{
			if(!isset($this->arenaName) || $this->arenaName === null)
			{
				$this->arenaName = $this->getXml()->getElementsByTagName('ArenaName')->item(0)->nodeValue;
			}
			return $this->arenaName;
		}
		return null;
	}

	/**
	 * Return match date
	 *
	 * @param String $format (php date() function format)
	 * @return String
	 */
	public function getDate($format = null)
	{
		if($this->isDataAvailable())
		{
			if(!isset($this->date) || $this->date === null)
			{
				$this->date = $this->getXml()->getElementsByTagName('MatchDate')->item(0)->nodeValue;
				if($format !== null)
				{
					$this->date = HTFunction::convertDate($this->date, $format);
				}
			}
			return $this->date;
		}
		return null;
	}

	/**
	 * Return match type
	 *
	 * @return Integer
	 */
	public function getType()
	{
		if($this->isDataAvailable())
		{
			if(!isset($this->type) || $this->type === null)
			{
				$this->type = $this->getXml()->getElementsByTagName('MatchType')->item(0)->nodeValue;
			}
			return $this->type;
		}
		return null;
	}

	/**
	 * Is attitude value available ?
	 *
	 * @return Boolean
	 */
	public function isAttituteAvailable()
	{
		if($this->isDataAvailable())
		{
			if(!isset($this->attitudeAvailable) || $this->attitudeAvailable === null)
			{
				$this->attitudeAvailable = strtolower($this->getXml()->getElementsByTagName('Attitude')->item(0)->getAttribute('Available')) == 'true';
			}
			return $this->attitudeAvailable;
		}
		return null;
	}

	/**
	 * Return attitude value
	 *
	 * @return Integer
	 */
	public function getAttitude()
	{
		if($this->isDataAvailable())
		{
			if($this->isAttituteAvailable())
			{
				if(!isset($this->attitude) || $this->attitude === null)
				{
					$this->attitude = $this->getXml()->getElementsByTagName('Attitude')->item(0)->nodeValue;
				}
				return $this->attitude;
			}
		}
		return null;
	}

	/**
	 * Return tactic value
	 *
	 * @return Integer
	 */
	public function getTactic()
	{
		if($this->isDataAvailable())
		{
			if(!isset($this->tactic) || $this->tactic === null)
			{
				$this->tactic = $this->getXml()->getElementsByTagName('TacticType')->item(0)->nodeValue;
			}
			return $this->tactic;
		}
		return null;
	}

	/**
	 * Return number players
	 *
	 * @return Integer
	 */
	public function getPlayersNumber()
	{
		if($this->isDataAvailable())
		{
			if(!isset($this->playersNumber) || $this->playersNumber === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//Player');
				$this->playersNumber = $nodeList->length;
			}
			return $this->playersNumber;
		}
		return null;
	}

	/**
	 * Return HTMatchOrdersPlayer object
	 *
	 * @param Integer $index
	 * @return HTMatchOrdersPlayer
	 */
	public function getPlayer($index)
	{
		if($this->isDataAvailable())
		{
			$index = round($index);
			if($index > 0 && $index <= $this->getPlayersNumber())
			{
				--$index;
				if(!isset($this->players[$index]) || $this->players[$index] === null)
				{
					$xpath = new DOMXPath($this->getXml());
					$nodeList = $xpath->query('//Player');
					$player = new DOMDocument('1.0', 'UTF-8');
					$player->appendChild($player->importNode($nodeList->item($index), true));
					$this->players[$index] = new HTMatchOrdersPlayer($player);
				}
				return $this->players[$index];
			}
		}
		return null;
	}

	/**
	 * Return HTMatchOrdersPlayer object
	 *
	 * @param Integer $index
	 * @return HTMatchOrdersPlayer
	 */
	public function getPlayerByRoleId($id)
	{
		if($this->isDataAvailable())
		{
			$id = round($id);
			if($id > 0 && $id < 19)
			{
				if(!isset($this->playersByRole[$id]) || $this->playersByRole[$id] === null)
				{
					$xpath = new DOMXPath($this->getXml());
					$nodeList = $xpath->query('//RoleID[.="'.$id.'"]');
					$player = new DOMDocument('1.0', 'UTF-8');
					$player->appendChild($player->importNode($nodeList->item(0)->parentNode, true));
					$this->playersByRole[$id] = new HTMatchOrdersPlayer($player);
				}
				return $this->playersByRole[$id];
			}
		}
		return null;
	}

	/**
	 * Return number player orders
	 *
	 * @return Integer
	 */
	public function getPlayerOrdersNumber()
	{
		if($this->isDataAvailable())
		{
			if(!isset($this->ordersNumber) || $this->ordersNumber === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//PlayerOrder');
				$this->ordersNumber = $nodeList->length;
			}
			return $this->ordersNumber;
		}
		return null;
	}

	/**
	 * Return HTMatchOrdersPlayerOrder object
	 *
	 * @param Integer $index
	 * @return HTMatchOrdersPlayerOrder
	 */
	public function getPlayerOrder($index)
	{
		if($this->isDataAvailable())
		{
			$index = round($index);
			if($index > 0 && $index <= $this->getPlayerOrdersNumber())
			{
				--$index;
				if(!isset($this->orders[$index]) || $this->orders[$index] === null)
				{
					$xpath = new DOMXPath($this->getXml());
					$nodeList = $xpath->query('//PlayerOrder');
					$order = new DOMDocument('1.0', 'UTF-8');
					$order->appendChild($order->importNode($nodeList->item($index), true));
					$this->orders[$index] = new HTMatchOrdersPlayerOrder($order);
				}
				return $this->orders[$index];
			}
		}
		return null;
	}

	/**
	 * Return number of orders for a player
	 *
	 * @param Integer $playerId
	 * @return Integer
	 */
	public function getPlayerOrderNumberByPlayerId($playerId)
	{
		if($this->isDataAvailable())
		{
			if(!isset($this->ordersNumberByPlayerId[$playerId]) || $this->ordersNumberByPlayerId[$playerId] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//SubjectPlayerID[.="'.$playerId.'"]');
				$this->ordersNumberByPlayerId[$playerId] = $nodeList->length;
			}
			return $this->ordersNumberByPlayerId[$playerId];
		}
		return 0;
	}

	/**
	 * Return HTMatchOrdersPlayerOrder object
	 *
	 * @param Integer $playerId
	 * @param Integer $number
	 * @return HTMatchOrdersPlayerOrder
	 */
	public function getPlayerOrderByPlayerId($playerId, $number = 1)
	{
		if($this->isDataAvailable())
		{
			if($number > 0 && $number <= $this->getPlayerOrderNumberByPlayerId($playerId))
			{
				--$number;
				if(!isset($this->ordersByPlayerId[$playerId]) || $this->ordersByPlayerId[$playerId] === null)
				{
					$xpath = new DOMXPath($this->getXml());
					$nodeList = $xpath->query('//SubjectPlayerID[.="'.$playerId.'"]');
					$order = new DOMDocument('1.0', 'UTF-8');
					$order->appendChild($order->importNode($nodeList->item($number)->parentNode, true));
					$this->ordersByPlayerId[$playerId] = new HTMatchOrdersPlayerOrder($order);
				}
				return $this->ordersByPlayerId[$playerId];
			}
		}
		return null;
	}
}
class HTMatchOrdersPlayer extends HTXml
{
	private $id = null;
	private $position = null;
	private $name = null;
	private $individualOrder = null;
	const UNAVAILABLE = 'unavailable';

	/**
	 * @param DOMDocument $xml
	 */
	public function __construct($xml)
	{
		$this->xmlText = $xml->saveXML();
		$this->xml = $xml;
	}

	/**
	 * Return player id
	 *
	 * @return Integer
	 */
	public function getId()
	{
		if(!isset($this->id) || $this->id === null)
		{
			$this->id = $this->getXml()->getElementsByTagName('PlayerID')->item(0)->nodeValue;
		}
		return $this->id;
	}

	/**
	 * Return player name
	 *
	 * @return String
	 */
	public function getName()
	{
		if(!isset($this->name) || $this->name === null)
		{
			$this->name = $this->getXml()->getElementsByTagName('PlayerName')->item(0)->nodeValue;
		}
		return $this->name;
	}

	/**
	 * Return position code
	 *
	 * @return Integer
	 */
	public function getPosition()
	{
		if(!isset($this->position) || $this->position === null)
		{
			$this->position = $this->getXml()->getElementsByTagName('RoleID')->item(0)->nodeValue;
		}
		return $this->position;
	}

	/**
	 * Return player individual order code
	 *
	 * @return Integer
	 */
	public function getIndividualOrder()
	{
		if(!isset($this->individualOrder) || $this->individualOrder === null)
		{
			$node = $this->getXml()->getElementsByTagName('Behaviour');
			if($node !== null && $node->length)
			{
				$this->individualOrder = $node->item(0)->nodeValue;
			}
			else
			{
				$this->individualOrder = self::UNAVAILABLE ;
			}
		}
		if($this->individualOrder === self::UNAVAILABLE )
		{
			return null;
		}
		return $this->individualOrder;
	}
}
class HTMatchOrdersPlayerOrder extends HTXml
{
	private $orderId = null;
	private $matchId = null;
	private $teamId = null;
	private $minuteCriteria = null;
	private $goalDiffCriteria = null;
	private $redCardCriteria = null;
	private $subjectPlayerId = null;
	private $objectPlayerId = null;
	private $extra = null;

	/**
	 * @param DOMDocument $xml
	 */
	public function __construct($xml)
	{
		$this->xmlText = $xml->saveXML();
		$this->xml = $xml;
	}

	/**
	 * Return player order id
	 *
	 * @return Integer
	 */
	public function getOrderId()
	{
		if(!isset($this->orderId) || $this->orderId === null)
		{
			$this->orderId = $this->getXml()->getElementsByTagName('PlayerOrderID')->item(0)->nodeValue;
		}
		return $this->orderId;
	}

	/**
	 * Return match id
	 *
	 * @return Integer
	 */
	public function getMatchId()
	{
		if(!isset($this->matchId) || $this->matchId === null)
		{
			$this->matchId = $this->getXml()->getElementsByTagName('MatchID')->item(0)->nodeValue;
		}
		return $this->matchId;
	}

	/**
	 * Return team id
	 *
	 * @return Integer
	 */
	public function getTeamId()
	{
		if(!isset($this->teamId) || $this->teamId === null)
		{
			$this->teamId = $this->getXml()->getElementsByTagName('TeamID')->item(0)->nodeValue;
		}
		return $this->teamId;
	}

	/**
	 * Return minute criteria
	 *
	 * @return Integer
	 */
	public function getMinuteCriteria()
	{
		if(!isset($this->minuteCriteria) || $this->minuteCriteria === null)
		{
			$this->minuteCriteria = $this->getXml()->getElementsByTagName('MatchMinuteCriteria')->item(0)->nodeValue;
		}
		return $this->minuteCriteria;
	}

	/**
	 * Return goal difference criteria
	 *
	 * @return Integer
	 */
	public function getGoalDifferenceCriteria()
	{
		if(!isset($this->goalDiffCriteria) || $this->goalDiffCriteria === null)
		{
			$this->goalDiffCriteria = $this->getXml()->getElementsByTagName('GoalDiffCriteria')->item(0)->nodeValue;
		}
		return $this->goalDiffCriteria;
	}

	/**
	 * Return red card criteria
	 *
	 * @return Integer
	 */
	public function getRedCardCriteria()
	{
		if(!isset($this->redCardCriteria) || $this->redCardCriteria === null)
		{
			$this->redCardCriteria = $this->getXml()->getElementsByTagName('RedCardCriteria')->item(0)->nodeValue;
		}
		return $this->redCardCriteria;
	}

	/**
	 * Return player id out
	 *
	 * @return Integer
	 */
	public function getPlayerIdOut()
	{
		if(!isset($this->subjectPlayerId) || $this->subjectPlayerId === null)
		{
			$this->subjectPlayerId = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerId;
	}

	/**
	 * Return player id in
	 *
	 * @return Integer
	 */
	public function getPlayerIdIn()
	{
		if(!isset($this->objectPlayerId) || $this->objectPlayerId === null)
		{
			$this->objectPlayerId = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerId;
	}

	/**
	 * Return new player behaviour
	 *
	 * @return Integer
	 */
	public function getNewBehaviour()
	{
		if(!isset($this->extra) || $this->extra === null)
		{
			$this->extra = $this->getXml()->getElementsByTagName('PlayerOrderExtraInteger')->item(0)->nodeValue;
		}
		return $this->extra;
	}
}
class HTArenaMyStats extends HTGlobal
{
	private $arenaId = null;
	private $arenaName = null;
	private $matchType = null;
	private $startDate = null;
	private $endDate = null;
	private $matchesNumber = null;
	private $average = null;
	private $most = null;
	private $least = null;
	const ALL = 'All';
	const COMP = 'CompOnly';
	const LEAGUE = 'LeagueOnly';
	const FRIENDLY = 'FriendlyOnly';

	/**
	 * Return arena id
	 *
	 * @return Integer
	 */
	public function getArenaId()
	{
		if(!isset($this->arenaId) || $this->arenaId === null)
		{
			$this->arenaId = $this->getXml()->getElementsByTagName('ArenaID')->item(0)->nodeValue;
		}
		return $this->arenaId;
	}

	/**
	 * Return arena name
	 *
	 * @return String
	 */
	public function getArenaName()
	{
		if(!isset($this->arenaName) || $this->arenaName === null)
		{
			$this->arenaName = $this->getXml()->getElementsByTagName('ArenaName')->item(0)->nodeValue;
		}
		return $this->arenaName;
	}

	/**
	 * Return match type
	 *
	 * @return String
	 */
	public function getMatchType()
	{
		if(!isset($this->matchType) || $this->matchType === null)
		{
			$this->matchType = $this->getXml()->getElementsByTagName('MatchTypes')->item(0)->nodeValue;
		}
		return $this->matchType;
	}

	/**
	 * Return start date
	 *
	 * @param String $format (php date() function format)
	 * @return String
	 */
	public function getStartDate($format = null)
	{
		if(!isset($this->startDate) || $this->startDate === null)
		{
			$this->startDate = $this->getXml()->getElementsByTagName('FirstDate')->item(0)->nodeValue;
			if($format !== null)
			{
				$this->startDate = HTFunction::convertDate($this->startDate, $format);
			}
		}
		return $this->startDate;
	}

	/**
	 * Return end date
	 *
	 * @param String $format (php date() function format)
	 * @return String
	 */
	public function getEndDate($format = null)
	{
		if(!isset($this->endDate) || $this->endDate === null)
		{
			$this->endDate = $this->getXml()->getElementsByTagName('LastDate')->item(0)->nodeValue;
			if($format !== null)
			{
				$this->endDate = HTFunction::convertDate($this->endDate, $format);
			}
		}
		return $this->endDate;
	}

	/**
	 * Return matches number
	 *
	 * @return String
	 */
	public function getMatchesNumber()
	{
		if(!isset($this->matchesNumber) || $this->matchesNumber === null)
		{
			$this->matchesNumber = $this->getXml()->getElementsByTagName('NumberOfMatches')->item(0)->nodeValue;
		}
		return $this->matchesNumber;
	}

	/**
	 * Return HTArenaMyStatsVisitors object
	 *
	 * @return HTArenaMyStatsVisitors
	 */
	public function getAverageVisitors()
	{
		if(!isset($this->average) || $this->average === null)
		{
			$xpath = new DOMXPath($this->getXml());
			$nodeList = $xpath->query('//VisitorsAverage');
			$visitor = new DOMDocument('1.0', 'UTF-8');
			$visitor->appendChild($visitor->importNode($nodeList->item(0), true));
			$this->average = new HTArenaMyStatsVisitors($visitor);
		}
		return $this->average;
	}

	/**
	 * Return HTArenaMyStatsVisitors object
	 *
	 * @return HTArenaMyStatsVisitors
	 */
	public function getMostVisitors()
	{
		if(!isset($this->most) || $this->most === null)
		{
			$xpath = new DOMXPath($this->getXml());
			$nodeList = $xpath->query('//VisitorsMost');
			$visitor = new DOMDocument('1.0', 'UTF-8');
			$visitor->appendChild($visitor->importNode($nodeList->item(0), true));
			$this->most = new HTArenaMyStatsVisitors($visitor);
		}
		return $this->most;
	}

	/**
	 * Return HTArenaMyStatsVisitors object
	 *
	 * @return HTArenaMyStatsVisitors
	 */
	public function getLeastVisitors()
	{
		if(!isset($this->least) || $this->least === null)
		{
			$xpath = new DOMXPath($this->getXml());
			$nodeList = $xpath->query('//VisitorsLeast');
			$visitor = new DOMDocument('1.0', 'UTF-8');
			$visitor->appendChild($visitor->importNode($nodeList->item(0), true));
			$this->least = new HTArenaMyStatsVisitors($visitor);
		}
		return $this->least;
	}
}
class HTArenaMyStatsVisitors extends HTXml
{
	private $terraces = null;
	private $basic = null;
	private $roof = null;
	private $vip = null;
	private $total = null;

	/**
	 * @param DOMDocument $xml
	 */
	public function __construct($xml)
	{
		$this->xmlText = $xml->saveXML();
		$this->xml = $xml;
	}

	/**
	 * Return terraces seats number
	 *
	 * @return Integer
	 */
	public function getTerraces()
	{
		if(!isset($this->terraces) || $this->terraces === null)
		{
			$this->terraces = $this->getXml()->getElementsByTagName('Terraces')->item(0)->nodeValue;
		}
		return $this->terraces;
	}

	/**
	 * Return basic seats number
	 *
	 * @return Integer
	 */
	public function getBasic()
	{
		if(!isset($this->basic) || $this->basic === null)
		{
			$this->basic = $this->getXml()->getElementsByTagName('Basic')->item(0)->nodeValue;
		}
		return $this->basic;
	}

	/**
	 * Return roof seats number
	 *
	 * @return Integer
	 */
	public function getRoof()
	{
		if(!isset($this->roof) || $this->roof === null)
		{
			$this->roof = $this->getXml()->getElementsByTagName('Roof')->item(0)->nodeValue;
		}
		return $this->roof;
	}

	/**
	 * Return vip seats number
	 *
	 * @return Integer
	 */
	public function getVip()
	{
		if(!isset($this->vip) || $this->vip === null)
		{
			$this->vip = $this->getXml()->getElementsByTagName('VIP')->item(0)->nodeValue;
		}
		return $this->vip;
	}

	/**
	 * Return total seats number
	 *
	 * @return Integer
	 */
	public function getTotal()
	{
		if(!isset($this->total) || $this->total === null)
		{
			$this->total = $this->getXml()->getElementsByTagName('Total')->item(0)->nodeValue;
		}
		return $this->total;
	}
}
class HTArenasStats extends HTGlobal
{
	private $leagueId = null;
	private $leagueName = null;
	private $createDate = null;
	private $arenaNumber = null;
	private $arenas = null;
	const UNAVAILABLE = 'unavailable';

	/**
	 * Return league id
	 *
	 * @return Integer
	 */
	public function getLeagueId()
	{
		if(!isset($this->leagueId) || $this->leagueId === null)
		{
			$this->leagueId = $this->getXml()->getElementsByTagName('LeagueID')->item(0)->nodeValue;
		}
		return $this->leagueId;
	}

	/**
	 * Return league name
	 *
	 * @return String
	 */
	public function getLeagueName()
	{
		if(!isset($this->leagueName) || $this->leagueName === null)
		{
			$node = $this->getXml()->getElementsByTagName('LeagueName');
			if($node !== null && $node->length)
			{
				$this->leagueName = $node->item(0)->nodeValue;
			}
			else
			{
				$this->leagueName = self::UNAVAILABLE;
			}
		}
		if($this->leagueName === self::UNAVAILABLE )
		{
			return null;
		}
		return $this->leagueName;
	}

	/**
	 * Return stats created date
	 *
	 * @param String $format (php date() function format)
	 * @return String
	 */
	public function getStatsDate($format = null)
	{
		if(!isset($this->createDate) || $this->createDate === null)
		{
			$this->createDate = $this->getXml()->getElementsByTagName('CreatedDate')->item(0)->nodeValue;
			if($format !== null)
			{
				$this->createDate = HTFunction::convertDate($this->createDate, $format);
			}
		}
		return $this->createDate;
	}

	/**
	 * Return number of arenas
	 *
	 * @return Integer
	 */
	public function getArenaNumber()
	{
		if(!isset($this->arenaNumber) || $this->arenaNumber === null)
		{
			$this->arenaNumber = $this->getXml()->getElementsByTagName('ArenaStat')->length;
		}
		return $this->arenaNumber;
	}

	/**
	 * Return HTArenasStatsArena object
	 *
	 * @param Integer $index
	 * @return HTArenasStatsArena
	 */
	public function getArena($index)
	{
		$index = round($index);
		if($index > 0 && $index <= $this->getArenaNumber())
		{
			--$index;
			if(!isset($this->arenas[$index]) || $this->arenas[$index] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//ArenaStat');
				$arena = new DOMDocument('1.0', 'UTF-8');
				$arena->appendChild($arena->importNode($nodeList->item($index), true));
				$this->arenas[$index] = new HTArenasStatsArena($arena);
			}
			return $this->arenas[$index];
		}
		return null;
	}
}
class HTArenasStatsArena extends HTXml
{
	private $id = null;
	private $name = null;
	private $size = null;
	private $regionId = null;
	private $regionName = null;

	/**
	 * @param DOMDocument $xml
	 */
	public function __construct($xml)
	{
		$this->xmlText = $xml->saveXML();
		$this->xml = $xml;
	}

	/**
	 * Return arena id
	 *
	 * @return Integer
	 */
	public function getId()
	{
		if(!isset($this->id) || $this->id === null)
		{
			$this->id = $this->getXml()->getElementsByTagName('ArenaID')->item(0)->nodeValue;
		}
		return $this->id;
	}

	/**
	 * Return arena name
	 *
	 * @return String
	 */
	public function getName()
	{
		if(!isset($this->name) || $this->name === null)
		{
			$this->name = $this->getXml()->getElementsByTagName('ArenaName')->item(0)->nodeValue;
		}
		return $this->name;
	}

	/**
	 * Return arena size
	 *
	 * @return Integer
	 */
	public function getSize()
	{
		if(!isset($this->size) || $this->size === null)
		{
			$this->size = $this->getXml()->getElementsByTagName('ArenaSize')->item(0)->nodeValue;
		}
		return $this->size;
	}

	/**
	 * Return arena region id
	 *
	 * @return Integer
	 */
	public function getRegionId()
	{
		if(!isset($this->regionId) || $this->regionId === null)
		{
			$this->regionId = $this->getXml()->getElementsByTagName('ArenaRegionID')->item(0)->nodeValue;
		}
		return $this->regionId;
	}

	/**
	 * Return arena region name
	 *
	 * @return String
	 */
	public function getRegionName()
	{
		if(!isset($this->regionName) || $this->regionName === null)
		{
			$this->regionName = $this->getXml()->getElementsByTagName('ArenaRegionName')->item(0)->nodeValue;
		}
		return $this->regionName;
	}
}
class HTChallanges extends HTCommonTeam
{
	private $mine = null;
	private $mineNumber = null;
	private $offersNumber = null;
	private $offers = null;

	/**
	 * Return my challanges number
	 *
	 * @return Integer
	 */
	public function getMineNumber()
	{
		if(!isset($this->mineNumber) || $this->mineNumber === null)
		{
			$xpath = new DOMXPath($this->getXml());
			$nodeList = $xpath->query('//Challenge');
			$this->mineNumber = $nodeList->length;
		}
		return $this->mineNumber;
	}

	/**
	 * Return HTChallange object
	 *
	 * @param Integer $index
	 * @return HTChallange
	 */
	public function getMine($index)
	{
		$index = round($index);
		if($index > 0 && $index <= $this->getMineNumber())
		{
			--$index;
			if(!isset($this->mine[$index]) || $this->mine[$index] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//Challenge');
				$challange = new DOMDocument('1.0', 'UTF-8');
				$challange->appendChild($challange->importNode($nodeList->item($index), true));
				$this->mine[$index] = new HTChallange($challange);
			}
			return $this->mine[$index];
		}
		return null;
	}

	/**
	 * Return offers challanges number
	 *
	 * @return Integer
	 */
	public function getOfferNumber()
	{
		if(!isset($this->offersNumber) || $this->offersNumber === null)
		{
			$xpath = new DOMXPath($this->getXml());
			$nodeList = $xpath->query('//Offer');
			$this->offersNumber = $nodeList->length;
		}
		return $this->offersNumber;
	}

	/**
	 * Return HTChallange object
	 *
	 * @param Integer $index
	 * @return HTChallange
	 */
	public function getOffer($index)
	{
		$index = round($index);
		if($index > 0 && $index <= $this->getOfferNumber())
		{
			--$index;
			if(!isset($this->offers[$index]) || $this->offers[$index] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//Offer');
				$challange = new DOMDocument('1.0', 'UTF-8');
				$challange->appendChild($challange->importNode($nodeList->item($index), true));
				$this->offers[$index] = new HTChallange($challange);
			}
			return $this->offers[$index];
		}
		return null;
	}
}
class HTChallange extends HTXml
{
	private $id = null;
	private $date = null;
	private $type = null;
	private $opponentTeamId = null;
	private $opponentTeamName = null;
	private $arenaId = null;
	private $arenaName = null;
	private $leagueId = null;
	private $leagueName = null;
	private $isAgreed = null;

	/**
	 * @param DOMDocument $xml
	 */
	public function __construct($xml)
	{
		$this->xmlText = $xml->saveXML();
		$this->xml = $xml;
	}

	/**
	 * Return challange id
	 *
	 * @return Integer
	 */
	public function getId()
	{
		if(!isset($this->id) || $this->id === null)
		{
			$this->id = $this->getXml()->getElementsByTagName('TrainingMatchID')->item(0)->nodeValue;
		}
		return $this->id;
	}

	/**
	 * Return match date
	 *
	 * @param String $format (php date() function format)
	 * @return String
	 */
	public function getMatchDate($format = null)
	{
		if(!isset($this->date) || $this->date === null)
		{
			$this->date = $this->getXml()->getElementsByTagName('MatchTime')->item(0)->nodeValue;
			if($format !== null)
			{
				$this->date = HTFunction::convertDate($this->date, $format);
			}
		}
		return $this->date;
	}

	/**
	 * Return match type
	 *
	 * @return Integer
	 */
	public function getMatchType()
	{
		if(!isset($this->type) || $this->type === null)
		{
			$this->type = $this->getXml()->getElementsByTagName('FriendlyType')->item(0)->nodeValue;
		}
		return $this->type;
	}

	/**
	 * Return opponent team id
	 *
	 * @return Integer
	 */
	public function getOppenentTeamId()
	{
		if(!isset($this->opponentTeamId) || $this->opponentTeamId === null)
		{
			$this->opponentTeamId = $this->getXml()->getElementsByTagName('TeamID')->item(0)->nodeValue;
		}
		return $this->opponentTeamId;
	}

	/**
	 * Return opponent team name
	 *
	 * @return Integer
	 */
	public function getOppenentTeamName()
	{
		if(!isset($this->opponentTeamName) || $this->opponentTeamName === null)
		{
			$this->opponentTeamName = $this->getXml()->getElementsByTagName('TeamName')->item(0)->nodeValue;
		}
		return $this->opponentTeamName;
	}

	/**
	 * Return arena id
	 *
	 * @return Integer
	 */
	public function getArenaId()
	{
		if(!isset($this->arenaId) || $this->arenaId === null)
		{
			$this->arenaId = $this->getXml()->getElementsByTagName('ArenaID')->item(0)->nodeValue;
		}
		return $this->arenaId;
	}

	/**
	 * Return arena name
	 *
	 * @return String
	 */
	public function getArenaName()
	{
		if(!isset($this->arenaName) || $this->arenaName === null)
		{
			$this->arenaName = $this->getXml()->getElementsByTagName('ArenaName')->item(0)->nodeValue;
		}
		return $this->arenaName;
	}

	/**
	 * Return league id
	 *
	 * @return Integer
	 */
	public function getLeagueId()
	{
		if(!isset($this->leagueId) || $this->leagueId === null)
		{
			$this->leagueId = $this->getXml()->getElementsByTagName('LeagueID')->item(0)->nodeValue;
		}
		return $this->leagueId;
	}

	/**
	 * Return league name
	 *
	 * @return String
	 */
	public function getLeagueName()
	{
		if(!isset($this->leagueName) || $this->leagueName === null)
		{
			$this->leagueName = $this->getXml()->getElementsByTagName('LeagueName')->item(0)->nodeValue;
		}
		return $this->leagueName;
	}

	/**
	 * Is this challange accepted ?
	 *
	 * @return Boolean
	 */
	public function isAccepted()
	{
		if(!isset($this->isAgreed) || $this->isAgreed === null)
		{
			$this->isAgreed = strtolower($this->getXml()->getElementsByTagName('IsAgreed')->item(0)->nodeValue) == "true";
		}
		return $this->isAgreed;
	}
}
class HTCup extends HTGlobal
{
	private $id = null;
	private $season = null;
	private $round = null;
	private $name = null;
	private $matchNumber = null;
	private $matches = null;

	/**
	 * Return cup id
	 *
	 * @return Integer
	 */
	public function getId()
	{
		if(!isset($this->id) || $this->id === null)
		{
			$this->id = $this->getXml()->getElementsByTagName('CupID')->item(0)->nodeValue;
		}
		return $this->id;
	}

	/**
	 * Return cup season
	 *
	 * @return Integer
	 */
	public function getSeason()
	{
		if(!isset($this->season) || $this->season === null)
		{
			$this->season = $this->getXml()->getElementsByTagName('CupSeason')->item(0)->nodeValue;
		}
		return $this->season;
	}

	/**
	 * Return cup round
	 *
	 * @return Integer
	 */
	public function getRound()
	{
		if(!isset($this->round) || $this->round === null)
		{
			$this->round = $this->getXml()->getElementsByTagName('CupRound')->item(0)->nodeValue;
		}
		return $this->round;
	}

	/**
	 * Return cup name
	 *
	 * @return String
	 */
	public function getName()
	{
		if(!isset($this->name) || $this->name === null)
		{
			$this->name = $this->getXml()->getElementsByTagName('CupName')->item(0)->nodeValue;
		}
		return $this->name;
	}

	/**
	 * Return match number
	 *
	 * @return Integer
	 */
	public function getMatchNumber()
	{
		if(!isset($this->matchNumber) || $this->matchNumber === null)
		{
			$this->matchNumber = $this->getXml()->getElementsByTagName('Match')->length;
		}
		return $this->matchNumber;
	}

	/**
	 * Return HTCupMatch object
	 *
	 * @param Integer $index
	 * @return HTCupMatch
	 */
	public function getMatch($index)
	{
		$index = round($index);
		if($index > 0 && $index <= $this->getMatchNumber())
		{
			--$index;
			if(!isset($this->matches[$index]) || $this->matches[$index] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//Match');
				$match = new DOMDocument('1.0', 'UTF-8');
				$match->appendChild($match->importNode($nodeList->item($index), true));
				$this->matches[$index] = new HTCupMatch($match);
			}
			return $this->matches[$index];
		}
		return null;
	}
}
class HTCupMatch extends HTXml
{
	private $id = null;
	private $date = null;
	private $homeTeamName = null;
	private $awayTeamName = null;
	private $matchResultsAvailable = null;
	private $homeGoals = null;
	private $awayGoals = null;
	private $leagueInfoAvailable = null;
	private $homeLeagueId = null;
	private $homeLeagueName = null;
	private $awayLeagueId = null;
	private $awayLeagueName = null;

	/**
	 * @param DOMDocument $xml
	 */
	public function __construct($xml)
	{
		$this->xmlText = $xml->saveXML();
		$this->xml = $xml;
	}

	/**
	 * Return match id
	 *
	 * @return Integer
	 */
	public function getId()
	{
		if(!isset($this->id) || $this->id === null)
		{
			$this->id = $this->getXml()->getElementsByTagName('MatchID')->item(0)->nodeValue;
		}
		return $this->id;
	}

	/**
	 * Return match date
	 *
	 * @param String $date (php date() function format)
	 * @return String
	 */
	public function getDate($format = null)
	{
		if(!isset($this->date) || $this->date === null)
		{
			$this->date = $this->getXml()->getElementsByTagName('MatchDate')->item(0)->nodeValue;
			if($format !== null)
			{
				$this->date = HTFunction::convertDate($this->date, $format);
			}
		}
		return $this->date;
	}

	/**
	 * Return home team name
	 *
	 * @return String
	 */
	public function getHomeTeamName()
	{
		if(!isset($this->homeTeamName) || $this->homeTeamName === null)
		{
			$this->homeTeamName = $this->getXml()->getElementsByTagName('HomeTeamName')->item(0)->nodeValue;
		}
		return $this->homeTeamName;
	}

	/**
	 * Return away team name
	 *
	 * @return String
	 */
	public function getAwayTeamName()
	{
		if(!isset($this->awayTeamName) || $this->awayTeamName === null)
		{
			$this->awayTeamName = $this->getXml()->getElementsByTagName('AwayTeamName')->item(0)->nodeValue;
		}
		return $this->awayTeamName;
	}

	/**
	 * Is match result available ?
	 *
	 * @return Boolean
	 */
	public function isMatchResultAvailable()
	{
		if(!isset($this->matchResultsAvailable) || $this->matchResultsAvailable === null)
		{
			$this->matchResultsAvailable = $this->getXml()->getElementsByTagName('MatchResult')->item(0)->getAttribute('Available') == 'true';
		}
		return $this->matchResultsAvailable;
	}

	/**
	 * Return home goals
	 *
	 * @return Integer
	 */
	public function getHomeGoals()
	{
		if($this->isMatchResultAvailable())
		{
			if(!isset($this->homeGoals) || $this->homeGoals === null)
			{
				$this->homeGoals = $this->getXml()->getElementsByTagName('HomeGoals')->item(0)->nodeValue;
			}
			return $this->homeGoals;
		}
		return null;
	}

	/**
	 * Return away goals
	 *
	 * @return Integer
	 */
	public function getAwayGoals()
	{
		if($this->isMatchResultAvailable())
		{
			if(!isset($this->awayGoals) || $this->awayGoals === null)
			{
				$this->awayGoals = $this->getXml()->getElementsByTagName('AwayGoals')->item(0)->nodeValue;
			}
			return $this->awayGoals;
		}
		return null;
	}

	/**
	 * Is league info available ?
	 *
	 * @return Boolean
	 */
	public function isLeagueInfoAvailable()
	{
		if(!isset($this->leagueInfoAvailable) || $this->leagueInfoAvailable === null)
		{
			$this->leagueInfoAvailable = strtolower($this->getXml()->getElementsByTagName('LeagueInfo')->item(0)->getAttribute('Available')) == 'true';
		}
		return $this->leagueInfoAvailable;
	}

	/**
	 * Return home league id
	 *
	 * @return Integer
	 */
	public function getHomeLeagueId()
	{
		if($this->isLeagueInfoAvailable())
		{
			if(!isset($this->homeLeagueId) || $this->homeLeagueId === null)
			{
				$this->homeLeagueId = $this->getXml()->getElementsByTagName('HomeLeagueID')->item(0)->nodeValue;
			}
			return $this->homeLeagueId;
		}
		return null;
	}

	/**
	 * Return away league id
	 *
	 * @return Integer
	 */
	public function getAwayLeagueId()
	{
		if($this->isLeagueInfoAvailable())
		{
			if(!isset($this->awayLeagueId) || $this->awayLeagueId === null)
			{
				$this->awayLeagueId = $this->getXml()->getElementsByTagName('AwayLeagueID')->item(0)->nodeValue;
			}
			return $this->awayLeagueId;
		}
		return null;
	}

	/**
	 * Return home league name
	 *
	 * @return Integer
	 */
	public function getHomeLeagueName()
	{
		if($this->isLeagueInfoAvailable())
		{
			if(!isset($this->homeLeagueName) || $this->homeLeagueName === null)
			{
				$this->homeLeagueName = $this->getXml()->getElementsByTagName('HomeLeagueName')->item(0)->nodeValue;
			}
			return $this->homeLeagueName;
		}
		return null;
	}

	/**
	 * Return away league name
	 *
	 * @return Integer
	 */
	public function getAwayLeagueName()
	{
		if($this->isLeagueInfoAvailable())
		{
			if(!isset($this->awayLeagueName) || $this->awayLeagueName === null)
			{
				$this->awayLeagueName = $this->getXml()->getElementsByTagName('AwayLeagueName')->item(0)->nodeValue;
			}
			return $this->awayLeagueName;
		}
		return null;
	}
}
class HTSearch extends HTGlobal
{
	private $searchString = null;
	private $searchString2 = null;
	private $searchId = null;
	private $searchLeagueId = null;
	private $type = null;
	private $resultNumber = null;
	private $results = null;
	private $pageIndex = null;
	private $pages = null;

	/**
	 * Return search string
	 *
	 * @return String
	 */
	public function getSearchString()
	{
		if(!isset($this->searchString) || $this->searchString === null)
		{
			$this->searchString = $this->getXml()->getElementsByTagName('SearchString')->item(0)->nodeValue;
		}
		return $this->searchString;
	}

	/**
	 * Return search string 2
	 *
	 * @return String
	 */
	public function getSearchString2()
	{
		if(!isset($this->searchString2) || $this->searchString2 === null)
		{
			$this->searchString2 = $this->getXml()->getElementsByTagName('SearchString2')->item(0)->nodeValue;
		}
		return $this->searchString2;
	}

	/**
	 * Return what type of search was performed
	 *
	 * @return Integer
	 */
	public function getSearchType()
	{
		if(!isset($this->type) || $this->type === null)
		{
			$this->type = $this->getXml()->getElementsByTagName('SearchType')->item(0)->nodeValue;
		}
		return $this->type;
	}

	/**
	 * Return search id
	 *
	 * @return Integer
	 */
	public function getSearchId()
	{
		if(!isset($this->searchId) || $this->searchId === null)
		{
			$this->searchId = $this->getXml()->getElementsByTagName('SearchID')->item(0)->nodeValue;
		}
		return $this->searchId;
	}

	/**
	 * Return search league id
	 *
	 * @return Integer
	 */
	public function getSearchLeagueId()
	{
		if(!isset($this->searchLeagueId) || $this->searchLeagueId === null)
		{
			$this->searchLeagueId = $this->getXml()->getElementsByTagName('SearchLeagueID')->item(0)->nodeValue;
		}
		return $this->searchLeagueId;
	}

	/**
	 * Return page index
	 *
	 * @return Integer
	 */
	public function getPageIndex()
	{
		if(!isset($this->pageIndex) || $this->pageIndex === null)
		{
			$this->pageIndex = $this->getXml()->getElementsByTagName('PageIndex')->item(0)->nodeValue;
		}
		return $this->pageIndex;
	}

	/**
	 * Return number page of results
	 *
	 * @return Integer
	 */
	public function getPageNumber()
	{
		if(!isset($this->pages) || $this->pages === null)
		{
			$this->pages = $this->getXml()->getElementsByTagName('Pages')->item(0)->nodeValue;
		}
		return $this->pages;
	}

	/**
	 * Return number of results
	 *
	 * @return String
	 */
	public function getResultNumber()
	{
		if(!isset($this->resultNumber) || $this->resultNumber === null)
		{
			$this->resultNumber = $this->getXml()->getElementsByTagName('Result')->length;
		}
		return $this->resultNumber;
	}

	/**
	 * Return HTSearchResult object
	 *
	 * @param Integer $index
	 * @return HTSearchResult
	 */
	public function getResult($index)
	{
		$index = round($index);
		if($index > 0 && $index <= $this->getResultNumber())
		{
			--$index;
			if(!isset($this->results[$index]) || $this->results[$index] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//Result');
				$result = new DOMDocument('1.0', 'UTF-8');
				$result->appendChild($result->importNode($nodeList->item($index), true));
				$this->results[$index] = new HTSearchResult($result);
			}
			return $this->results[$index];
		}
		return null;
	}
}
class HTSearchResult extends HTXml
{
	private $id = null;
	private $name = null;

	/**
	 * @param DOMDocument $xml
	 */
	public function __construct($xml)
	{
		$this->xmlText = $xml->saveXML();
		$this->xml = $xml;
	}

	/**
	 * Return result id
	 *
	 * @return Integer
	 */
	public function getId()
	{
		if(!isset($this->id) || $this->id === null)
		{
			$this->id = $this->getXml()->getElementsByTagName('ResultID')->item(0)->nodeValue;
		}
		return $this->id;
	}

	/**
	 * Return result value
	 *
	 * @return String
	 */
	public function getValue()
	{
		if(!isset($this->name) || $this->name === null)
		{
			$this->name = $this->getXml()->getElementsByTagName('ResultName')->item(0)->nodeValue;
		}
		return $this->name;
	}
}
class HTBookmarksGlobal extends HTGlobal
{
	const ALL = 0;
	const TEAMS = 1;
	const PLAYERS = 2;
	const MATCHES = 3;
	const CONFUSERS = 4;
	const LEAGUES = 5;
	const YOUTHTEAMS = 6;
	const YOUTHPLAYERS = 7;
	const YOUTHMATCHS = 8;
	const YOUTHLEAGUES = 9;
	const CONFPOSTS = 10;
	const CONFTHREADS = 11;

	/**
	 * Return number of bookmarks
	 *
	 * @return Integer
	 */
	public function getBookarksNumber()
	{
		if(!isset($this->bookmarksNumber) || $this->bookmarksNumber === null)
		{
			$this->bookmarksNumber = $this->getXml()->getElementsByTagName('Bookmark')->length;
		}
		return $this->bookmarksNumber;
	}
}
class HTBookmarks extends HTBookmarksGlobal
{
	protected $bookmarksNumber = null;
	private $bookmarks = null;

	/**
	 * Return HTBookmark object
	 *
	 * @param Integer $index
	 * @return HTBookmark
	 */
	public function getBookmark($index)
	{
		$index = round($index);
		if($index > 0 && $index <= $this->getBookarksNumber())
		{
			--$index;
			if(!isset($this->bookmarks[$index]) || $this->bookmarks[$index] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//Bookmark');
				$bookmark = new DOMDocument('1.0', 'UTF-8');
				$bookmark->appendChild($bookmark->importNode($nodeList->item($index), true));
				$this->bookmarks[$index] = new HTBookmark($bookmark);
			}
			return $this->bookmarks[$index];
		}
		return null;
	}
}
class HTBookmarkGlobal extends HTXml
{
	protected $id = null;
	protected $type = null;
	protected $comment = null;

	/**
	 * @param DOMDocument $xml
	 */
	public function __construct($xml)
	{
		$this->xmlText = $xml->saveXML();
		$this->xml = $xml;
	}

	/**
	 * Return bookmark id
	 *
	 * @return Integer
	 */
	public function getId()
	{
		if(!isset($this->id) || $this->id === null)
		{
			$this->id = $this->getXml()->getElementsByTagName('BookmarkID')->item(0)->nodeValue;
		}
		return $this->id;
	}

	/**
	 * Return bookmark type
	 *
	 * @return Integer
	 */
	public function getType()
	{
		if(!isset($this->type) || $this->type === null)
		{
			$this->type = $this->getXml()->getElementsByTagName('BookmarkTypeID')->item(0)->nodeValue;
		}
		return $this->type;
	}

	/**
	 * Return bookmark comment
	 *
	 * @return String
	 */
	public function getComment()
	{
		if(!isset($this->comment) || $this->comment === null)
		{
			$this->comment = $this->getXml()->getElementsByTagName('Comment')->item(0)->nodeValue;
		}
		return $this->comment;
	}
}
class HTBookmark extends HTBookmarkGlobal
{
	private $text = null;
	private $text2 = null;
	private $objectId = null;
	private $objectId2 = null;
	private $objectId3 = null;

	/**
	 * Return bookmark text
	 *
	 * @return String
	 */
	public function getText()
	{
		if(!isset($this->text) || $this->text === null)
		{
			$this->text = $this->getXml()->getElementsByTagName('Text')->item(0)->nodeValue;
		}
		return $this->text;
	}

	/**
	 * Return bookmark text2
	 *
	 * @return String
	 */
	public function getText2()
	{
		if(!isset($this->text2) || $this->text2 === null)
		{
			$this->text2 = $this->getXml()->getElementsByTagName('Text2')->item(0)->nodeValue;
		}
		return $this->text2;
	}

	/**
	 * Return bookmark objectId
	 *
	 * @return Integer
	 */
	public function getObjectId()
	{
		if(!isset($this->objectId) || $this->objectId === null)
		{
			$this->objectId = $this->getXml()->getElementsByTagName('ObjectID')->item(0)->nodeValue;
		}
		return $this->objectId;
	}

	/**
	 * Return bookmark objectId2
	 *
	 * @return Integer
	 */
	public function getObjectId2()
	{
		if(!isset($this->objectId2) || $this->objectId2 === null)
		{
			$this->objectId2 = $this->getXml()->getElementsByTagName('ObjectID2')->item(0)->nodeValue;
		}
		return $this->objectId2;
	}

	/**
	 * Return bookmark objectId3
	 *
	 * @return Integer
	 */
	public function getObjectId3()
	{
		if(!isset($this->objectId3) || $this->objectId3 === null)
		{
			$this->objectId3 = $this->getXml()->getElementsByTagName('ObjectID3')->item(0)->nodeValue;
		}
		return $this->objectId3;
	}
}
class HTBookmarksTeams extends HTBookmarksGlobal
{
	protected $bookmarksNumber = null;
	private $bookmarks = null;

	/**
	 * Return HTBookmarkTeam object
	 *
	 * @param Integer $index
	 * @return HTBookmarkTeam
	 */
	public function getBookmark($index)
	{
		$index = round($index);
		if($index > 0 && $index <= $this->getBookarksNumber())
		{
			--$index;
			if(!isset($this->bookmarks[$index]) || $this->bookmarks[$index] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//Bookmark');
				$bookmark = new DOMDocument('1.0', 'UTF-8');
				$bookmark->appendChild($bookmark->importNode($nodeList->item($index), true));
				$this->bookmarks[$index] = new HTBookmarkTeam($bookmark);
			}
			return $this->bookmarks[$index];
		}
		return null;
	}
}
class HTBookmarkTeam extends HTBookmarkGlobal
{
	private $teamName = null;
	private $teamId = null;
	private $alias = null;

	/**
	 * Return team name
	 *
	 * @return String
	 */
	public function getTeamName()
	{
		if(!isset($this->teamName) || $this->teamName === null)
		{
			$this->teamName = $this->getXml()->getElementsByTagName('Text')->item(0)->nodeValue;
		}
		return $this->teamName;
	}

	/**
	 * Return team id
	 *
	 * @return Integer
	 */
	public function getTeamId()
	{
		if(!isset($this->teamId) || $this->teamId === null)
		{
			$this->teamId = $this->getXml()->getElementsByTagName('ObjectID')->item(0)->nodeValue;
		}
		return $this->teamId;
	}

	/**
	 * Return team user's alias
	 *
	 * @return String
	 */
	public function getAlias()
	{
		if(!isset($this->alias) || $this->alias === null)
		{
			$this->alias = $this->getXml()->getElementsByTagName('Text2')->item(0)->nodeValue;
		}
		return $this->alias;
	}
}
class HTBookmarksPlayers extends HTBookmarksGlobal
{
	protected $bookmarksNumber = null;
	private $bookmarks = null;

	/**
	 * Return HTBookmarkPlayer object
	 *
	 * @param Integer $index
	 * @return HTBookmarkPlayer
	 */
	public function getBookmark($index)
	{
		$index = round($index);
		if($index > 0 && $index <= $this->getBookarksNumber())
		{
			--$index;
			if(!isset($this->bookmarks[$index]) || $this->bookmarks[$index] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//Bookmark');
				$bookmark = new DOMDocument('1.0', 'UTF-8');
				$bookmark->appendChild($bookmark->importNode($nodeList->item($index), true));
				$this->bookmarks[$index] = new HTBookmarkPlayer($bookmark);
			}
			return $this->bookmarks[$index];
		}
		return null;
	}
}
class HTBookmarkPlayer extends HTBookmarkGlobal
{
	private $playerName = null;
	private $playerId = null;
	private $deadline = null;

	/**
	 * Return player name
	 *
	 * @return String
	 */
	public function getPlayerName()
	{
		if(!isset($this->playerName) || $this->playerName === null)
		{
			$this->playerName = $this->getXml()->getElementsByTagName('Text')->item(0)->nodeValue;
		}
		return $this->playerName;
	}

	/**
	 * Return player id
	 *
	 * @return Integer
	 */
	public function getPlayerId()
	{
		if(!isset($this->playerId) || $this->playerId === null)
		{
			$this->playerId = $this->getXml()->getElementsByTagName('ObjectID')->item(0)->nodeValue;
		}
		return $this->playerId;
	}

	/**
	 * Return player deadline
	 *
	 * @param String $format (php date() function format)
	 * @return String
	 */
	public function getDeadline($format = null)
	{
		if(!isset($this->deadline) || $this->deadline === null)
		{
			$this->deadline = $this->getXml()->getElementsByTagName('Text2')->item(0)->nodeValue;
			if($format !== null)
			{
				$this->deadline = HTFunction::convertDate($this->deadline, $format);
			}
		}
		return $this->deadline;
	}
}
class HTBookmarksMatches extends HTBookmarksGlobal
{
	protected $bookmarksNumber = null;
	private $bookmarks = null;

	/**
	 * Return HTBookmarkMatch object
	 *
	 * @param Integer $index
	 * @return HTBookmarkMatch
	 */
	public function getBookmark($index)
	{
		$index = round($index);
		if($index > 0 && $index <= $this->getBookarksNumber())
		{
			--$index;
			if(!isset($this->bookmarks[$index]) || $this->bookmarks[$index] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//Bookmark');
				$bookmark = new DOMDocument('1.0', 'UTF-8');
				$bookmark->appendChild($bookmark->importNode($nodeList->item($index), true));
				$this->bookmarks[$index] = new HTBookmarkMatch($bookmark);
			}
			return $this->bookmarks[$index];
		}
		return null;
	}
}
class HTBookmarkMatch extends HTBookmarkGlobal
{
	private $teams = null;
	private $matchDate = null;
	private $matchId = null;

	/**
	 * Return teams names
	 *
	 * @return String
	 */
	public function getTeams()
	{
		if(!isset($this->teams) || $this->teams === null)
		{
			$this->teams = $this->getXml()->getElementsByTagName('Text')->item(0)->nodeValue;
		}
		return $this->teams;
	}

	/**
	 * Return match id
	 *
	 * @return Integer
	 */
	public function getMatchId()
	{
		if(!isset($this->matchId) || $this->matchId === null)
		{
			$this->matchId = $this->getXml()->getElementsByTagName('ObjectID')->item(0)->nodeValue;
		}
		return $this->matchId;
	}

	/**
	 * Return match date
	 *
	 * @param String $format (php date() function format)
	 * @return String
	 */
	public function getMatchDate($format = null)
	{
		if(!isset($this->matchDate) || $this->matchDate === null)
		{
			$this->matchDate = $this->getXml()->getElementsByTagName('Text2')->item(0)->nodeValue;
			if($format !== null)
			{
				$this->matchDate = HTFunction::convertDate($this->matchDate, $format);
			}
		}
		return $this->matchDate;
	}
}
class HTBookmarksConfUsers extends HTBookmarksGlobal
{
	protected $bookmarksNumber = null;
	private $bookmarks = null;

	/**
	 * Return HTBookmarkConfUser object
	 *
	 * @param Integer $index
	 * @return HTBookmarkConfUser
	 */
	public function getBookmark($index)
	{
		$index = round($index);
		if($index > 0 && $index <= $this->getBookarksNumber())
		{
			--$index;
			if(!isset($this->bookmarks[$index]) || $this->bookmarks[$index] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//Bookmark');
				$bookmark = new DOMDocument('1.0', 'UTF-8');
				$bookmark->appendChild($bookmark->importNode($nodeList->item($index), true));
				$this->bookmarks[$index] = new HTBookmarkConfUser($bookmark);
			}
			return $this->bookmarks[$index];
		}
		return null;
	}
}
class HTBookmarkConfUser extends HTBookmarkGlobal
{
	private $alias = null;
	private $userId = null;

	/**
	 * Return user alias
	 *
	 * @return String
	 */
	public function getAlias()
	{
		if(!isset($this->alias) || $this->alias === null)
		{
			$this->alias = $this->getXml()->getElementsByTagName('Text')->item(0)->nodeValue;
		}
		return $this->alias;
	}

	/**
	 * Return user id
	 *
	 * @return Integer
	 */
	public function getUserId()
	{
		if(!isset($this->userId) || $this->userId === null)
		{
			$this->userId = $this->getXml()->getElementsByTagName('ObjectID')->item(0)->nodeValue;
		}
		return $this->userId;
	}
}
class HTBookmarksLeagues extends HTBookmarksGlobal
{
	protected $bookmarksNumber = null;
	private $bookmarks = null;

	/**
	 * Return HTBookmarkLeague object
	 *
	 * @param Integer $index
	 * @return HTBookmarkLeague
	 */
	public function getBookmark($index)
	{
		$index = round($index);
		if($index > 0 && $index <= $this->getBookarksNumber())
		{
			--$index;
			if(!isset($this->bookmarks[$index]) || $this->bookmarks[$index] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//Bookmark');
				$bookmark = new DOMDocument('1.0', 'UTF-8');
				$bookmark->appendChild($bookmark->importNode($nodeList->item($index), true));
				$this->bookmarks[$index] = new HTBookmarkLeague($bookmark);
			}
			return $this->bookmarks[$index];
		}
		return null;
	}
}
class HTBookmarkLeague extends HTBookmarkGlobal
{
	private $leagueName = null;
	private $leagueId = null;
	private $leagueLevelId = null;

	/**
	 * Return league name
	 *
	 * @return String
	 */
	public function getLeagueName()
	{
		if(!isset($this->leagueName) || $this->leagueName === null)
		{
			$this->leagueName = $this->getXml()->getElementsByTagName('Text')->item(0)->nodeValue;
		}
		return $this->leagueName;
	}

	/**
	 * Return league level id
	 *
	 * @return Integer
	 */
	public function getLeagueLevelId()
	{
		if(!isset($this->leagueLevelId) || $this->leagueLevelId === null)
		{
			$this->leagueLevelId = $this->getXml()->getElementsByTagName('ObjectID')->item(0)->nodeValue;
		}
		return $this->leagueLevelId;
	}

	/**
	 * Return league id
	 *
	 * @return Integer
	 */
	public function getLeagueId()
	{
		if(!isset($this->leagueId) || $this->leagueId === null)
		{
			$this->leagueId = $this->getXml()->getElementsByTagName('ObjectID2')->item(0)->nodeValue;
		}
		return $this->leagueId;
	}
}
class HTBookmarksYouthTeams extends HTBookmarksGlobal
{
	protected $bookmarksNumber = null;
	private $bookmarks = null;

	/**
	 * Return HTBookmarkYouthTeam object
	 *
	 * @param Integer $index
	 * @return HTBookmarkYouthTeam
	 */
	public function getBookmark($index)
	{
		$index = round($index);
		if($index > 0 && $index <= $this->getBookarksNumber())
		{
			--$index;
			if(!isset($this->bookmarks[$index]) || $this->bookmarks[$index] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//Bookmark');
				$bookmark = new DOMDocument('1.0', 'UTF-8');
				$bookmark->appendChild($bookmark->importNode($nodeList->item($index), true));
				$this->bookmarks[$index] = new HTBookmarkYouthTeam($bookmark);
			}
			return $this->bookmarks[$index];
		}
		return null;
	}
}
class HTBookmarkYouthTeam extends HTBookmarkGlobal
{
	private $teamName = null;
	private $teamId = null;

	/**
	 * Return team name
	 *
	 * @return String
	 */
	public function getYouthTeamName()
	{
		if(!isset($this->teamName) || $this->teamName === null)
		{
			$this->teamName = $this->getXml()->getElementsByTagName('Text')->item(0)->nodeValue;
		}
		return $this->teamName;
	}

	/**
	 * Return team id
	 *
	 * @return Integer
	 */
	public function getYouthTeamId()
	{
		if(!isset($this->teamId) || $this->teamId === null)
		{
			$this->teamId = $this->getXml()->getElementsByTagName('ObjectID')->item(0)->nodeValue;
		}
		return $this->teamId;
	}
}
class HTBookmarksYouthPlayers extends HTBookmarksGlobal
{
	protected $bookmarksNumber = null;
	private $bookmarks = null;

	/**
	 * Return HTBookmarkYouthPlayer object
	 *
	 * @param Integer $index
	 * @return HTBookmarkYouthPlayer
	 */
	public function getBookmark($index)
	{
		$index = round($index);
		if($index > 0 && $index <= $this->getBookarksNumber())
		{
			--$index;
			if(!isset($this->bookmarks[$index]) || $this->bookmarks[$index] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//Bookmark');
				$bookmark = new DOMDocument('1.0', 'UTF-8');
				$bookmark->appendChild($bookmark->importNode($nodeList->item($index), true));
				$this->bookmarks[$index] = new HTBookmarkYouthPlayer($bookmark);
			}
			return $this->bookmarks[$index];
		}
		return null;
	}
}
class HTBookmarkYouthPlayer extends HTBookmarkGlobal
{
	private $playerName = null;
	private $playerId = null;

	/**
	 * Return player name
	 *
	 * @return String
	 */
	public function getYouthPlayerName()
	{
		if(!isset($this->playerName) || $this->playerName === null)
		{
			$this->playerName = $this->getXml()->getElementsByTagName('Text')->item(0)->nodeValue;
		}
		return $this->playerName;
	}

	/**
	 * Return player id
	 *
	 * @return Integer
	 */
	public function getYouthPlayerId()
	{
		if(!isset($this->playerId) || $this->playerId === null)
		{
			$this->playerId = $this->getXml()->getElementsByTagName('ObjectID')->item(0)->nodeValue;
		}
		return $this->playerId;
	}
}
class HTBookmarksYouthMatches extends HTBookmarksGlobal
{
	protected $bookmarksNumber = null;
	private $bookmarks = null;

	/**
	 * Return HTBookmarkYouthMatch object
	 *
	 * @param Integer $index
	 * @return HTBookmarkYouthMatch
	 */
	public function getBookmark($index)
	{
		$index = round($index);
		if($index > 0 && $index <= $this->getBookarksNumber())
		{
			--$index;
			if(!isset($this->bookmarks[$index]) || $this->bookmarks[$index] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//Bookmark');
				$bookmark = new DOMDocument('1.0', 'UTF-8');
				$bookmark->appendChild($bookmark->importNode($nodeList->item($index), true));
				$this->bookmarks[$index] = new HTBookmarkYouthMatch($bookmark);
			}
			return $this->bookmarks[$index];
		}
		return null;
	}
}
class HTBookmarkYouthMatch extends HTBookmarkGlobal
{
	private $teams = null;
	private $matchDate = null;
	private $matchId = null;

	/**
	 * Return youth teams names
	 *
	 * @return String
	 */
	public function getYouthTeams()
	{
		if(!isset($this->teams) || $this->teams === null)
		{
			$this->teams = $this->getXml()->getElementsByTagName('Text')->item(0)->nodeValue;
		}
		return $this->teams;
	}

	/**
	 * Return match id
	 *
	 * @return Integer
	 */
	public function getYouthMatchId()
	{
		if(!isset($this->matchId) || $this->matchId === null)
		{
			$this->matchId = $this->getXml()->getElementsByTagName('ObjectID')->item(0)->nodeValue;
		}
		return $this->matchId;
	}

	/**
	 * Return match date
	 *
	 * @param String $format (php date() function format)
	 * @return String
	 */
	public function getYouthMatchDate($format = null)
	{
		if(!isset($this->matchDate) || $this->matchDate === null)
		{
			$this->matchDate = $this->getXml()->getElementsByTagName('Text2')->item(0)->nodeValue;
			if($format !== null)
			{
				$this->matchDate = HTFunction::convertDate($this->matchDate, $format);
			}
		}
		return $this->matchDate;
	}
}
class HTBookmarksYouthLeagues extends HTBookmarksGlobal
{
	protected $bookmarksNumber = null;
	private $bookmarks = null;

	/**
	 * Return HTBookmarkYouthLeague object
	 *
	 * @param Integer $index
	 * @return HTBookmarkYouthLeague
	 */
	public function getBookmark($index)
	{
		$index = round($index);
		if($index > 0 && $index <= $this->getBookarksNumber())
		{
			--$index;
			if(!isset($this->bookmarks[$index]) || $this->bookmarks[$index] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//Bookmark');
				$bookmark = new DOMDocument('1.0', 'UTF-8');
				$bookmark->appendChild($bookmark->importNode($nodeList->item($index), true));
				$this->bookmarks[$index] = new HTBookmarkYouthLeague($bookmark);
			}
			return $this->bookmarks[$index];
		}
		return null;
	}
}
class HTBookmarkYouthLeague extends HTBookmarkGlobal
{
	private $leagueName = null;
	private $leagueId = null;

	/**
	 * Return league name
	 *
	 * @return String
	 */
	public function getYouthLeagueName()
	{
		if(!isset($this->leagueName) || $this->leagueName === null)
		{
			$this->leagueName = $this->getXml()->getElementsByTagName('Text')->item(0)->nodeValue;
		}
		return $this->leagueName;
	}

	/**
	 * Return league id
	 *
	 * @return Integer
	 */
	public function getYouthLeagueId()
	{
		if(!isset($this->leagueId) || $this->leagueId === null)
		{
			$this->leagueId = $this->getXml()->getElementsByTagName('ObjectID')->item(0)->nodeValue;
		}
		return $this->leagueId;
	}
}
class HTBookmarksConfPosts extends HTBookmarksGlobal
{
	protected $bookmarksNumber = null;
	private $bookmarks = null;

	/**
	 * Return HTBookmarkConfPost object
	 *
	 * @param Integer $index
	 * @return HTBookmarkConfPost
	 */
	public function getBookmark($index)
	{
		$index = round($index);
		if($index > 0 && $index <= $this->getBookarksNumber())
		{
			--$index;
			if(!isset($this->bookmarks[$index]) || $this->bookmarks[$index] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//Bookmark');
				$bookmark = new DOMDocument('1.0', 'UTF-8');
				$bookmark->appendChild($bookmark->importNode($nodeList->item($index), true));
				$this->bookmarks[$index] = new HTBookmarkConfPost($bookmark);
			}
			return $this->bookmarks[$index];
		}
		return null;
	}
}
class HTBookmarkConfPost extends HTBookmarkGlobal
{
	private $subject = null;
	private $poster = null;
	private $threadId = null;
	private $userId = null;

	/**
	 * Return post subject
	 *
	 * @return String
	 */
	public function getSubject()
	{
		if(!isset($this->subject) || $this->subject === null)
		{
			$this->subject = $this->getXml()->getElementsByTagName('Text')->item(0)->nodeValue;
		}
		return $this->subject;
	}

	/**
	 * Return poster name
	 *
	 * @return String
	 */
	public function getPoster()
	{
		if(!isset($this->poster) || $this->poster === null)
		{
			$this->poster = $this->getXml()->getElementsByTagName('Text2')->item(0)->nodeValue;
		}
		return $this->poster;
	}

	/**
	 * Return thread id
	 *
	 * @return Integer
	 */
	public function getThreadId()
	{
		if(!isset($this->threadId) || $this->threadId === null)
		{
			$this->threadId = $this->getXml()->getElementsByTagName('ObjectID')->item(0)->nodeValue;
		}
		return $this->threadId;
	}

	/**
	 * Return poster user id
	 *
	 * @return Integer
	 */
	public function getUserId()
	{
		if(!isset($this->userId) || $this->userId === null)
		{
			$this->userId = $this->getXml()->getElementsByTagName('ObjectID2')->item(0)->nodeValue;
		}
		return $this->userId;
	}
}
class HTBookmarksConfThreads extends HTBookmarksGlobal
{
	protected $bookmarksNumber = null;
	private $bookmarks = null;

	/**
	 * Return HTBookmarkConfThread object
	 *
	 * @param Integer $index
	 * @return HTBookmarkConfThread
	 */
	public function getBookmark($index)
	{
		$index = round($index);
		if($index > 0 && $index <= $this->getBookarksNumber())
		{
			--$index;
			if(!isset($this->bookmarks[$index]) || $this->bookmarks[$index] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//Bookmark');
				$bookmark = new DOMDocument('1.0', 'UTF-8');
				$bookmark->appendChild($bookmark->importNode($nodeList->item($index), true));
				$this->bookmarks[$index] = new HTBookmarkConfThread($bookmark);
			}
			return $this->bookmarks[$index];
		}
		return null;
	}
}
class HTBookmarkConfThread extends HTBookmarkGlobal
{
	private $subject = null;
	private $poster = null;
	private $threadId = null;
	private $userId = null;
	private $messageId = null;

	/**
	 * Return post subject
	 *
	 * @return String
	 */
	public function getSubject()
	{
		if(!isset($this->subject) || $this->subject === null)
		{
			$this->subject = $this->getXml()->getElementsByTagName('Text')->item(0)->nodeValue;
		}
		return $this->subject;
	}

	/**
	 * Return poster name
	 *
	 * @return String
	 */
	public function getPoster()
	{
		if(!isset($this->poster) || $this->poster === null)
		{
			$this->poster = $this->getXml()->getElementsByTagName('Text2')->item(0)->nodeValue;
		}
		return $this->poster;
	}

	/**
	 * Return thread id
	 *
	 * @return Integer
	 */
	public function getThreadId()
	{
		if(!isset($this->threadId) || $this->threadId === null)
		{
			$this->threadId = $this->getXml()->getElementsByTagName('ObjectID')->item(0)->nodeValue;
		}
		return $this->threadId;
	}

	/**
	 * Return message id
	 *
	 * @return Integer
	 */
	public function getMessageId()
	{
		if(!isset($this->messageId) || $this->messageId === null)
		{
			$this->messageId = $this->getXml()->getElementsByTagName('ObjectID2')->item(0)->nodeValue;
		}
		return $this->messageId;
	}

	/**
	 * Return poster user id
	 *
	 * @return Integer
	 */
	public function getUserId()
	{
		if(!isset($this->userId) || $this->userId === null)
		{
			$this->userId = $this->getXml()->getElementsByTagName('ObjectID3')->item(0)->nodeValue;
		}
		return $this->userId;
	}
}
class HTTrainingEvents extends HTGlobal
{
	private $playerId = null;
	private $dataAvailable = null;
	private $eventsNumber = null;
	private $eventsAvailable = null;
	private $events = null;

	/**
	 * Is data available for this player ?
	 *
	 * @return Boolean
	 */
	public function isDataAvailable()
	{
		if(!isset($this->dataAvailable) || $this->dataAvailable === null)
		{
			$this->dataAvailable = strtolower($this->getXml()->getElementsByTagName('ActionResult')->item(0)->getAttribute('Success')) == 'true';
		}
		return $this->dataAvailable;
	}

	/**
	 * Is events available for this player ?
	 *
	 * @return Boolean
	 */
	public function isEventsAvailable()
	{
		if(!isset($this->eventsAvailable) || $this->eventsAvailable === null)
		{
			$this->eventsAvailable = strtolower($this->getXml()->getElementsByTagName('TrainingEvents')->item(0)->getAttribute('Available')) == 'true';
		}
		return $this->eventsAvailable;
	}

	/**
	 * Return player id
	 *
	 * @return Integer
	 */
	public function getPlayerId()
	{
		if(!isset($this->playerId) || $this->playerId === null)
		{
			$this->playerId = $this->getXml()->getElementsByTagName('PlayerID')->item(0)->nodeValue;
		}
		return $this->playerId;
	}

	/**
	 * Return number of training events
	 *
	 * @return Integer
	 */
	public function getEventsNumber()
	{
		if($this->isDataAvailable() && $this->isEventsAvailable())
		{
			if(!isset($this->eventsNumber) || $this->eventsNumber === null)
			{
				$this->eventsNumber = $this->getXml()->getElementsByTagName('TrainingEvent')->length;
			}
			return $this->eventsNumber;
		}
		return null;
	}

	/**
	 * Return HTTrainingEvent object
	 *
	 * @param Integer $index
	 * @return HTTrainingEvent
	 */
	public function getEvent($index)
	{
		$index = round($index);
		if($index > 0 && $index <= $this->getEventsNumber())
		{
			--$index;
			if(!isset($this->events[$index]) || $this->events[$index] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//TrainingEvent');
				$event = new DOMDocument('1.0', 'UTF-8');
				$event->appendChild($event->importNode($nodeList->item($index), true));
				$this->events[$index] = new HTTrainingEvent($event);
			}
			return $this->events[$index];
		}
		return null;
	}
}
class HTTrainingEvent extends HTXml
{
	private $date = null;
	private $skillId = null;
	private $oldLevel = null;
	private $newLevel = null;
	private $season = null;
	private $matchRound = null;
	private $day = null;

	/**
	 * @param DOMDocument $xml
	 */
	public function __construct($xml)
	{
		$this->xmlText = $xml->saveXML();
		$this->xml = $xml;
	}

	/**
	 * Return event date
	 *
	 * @param String $format (php date() function format)
	 * @return String
	 */
	public function getDate($format = null)
	{
		if(!isset($this->date) || $this->date === null)
		{
			$this->date = $this->getXml()->getElementsByTagName('EventDate')->item(0)->nodeValue;
			if($format !== null)
			{
				$this->date = HTFunction::convertDate($this->date, $format);
			}
		}
		return $this->date;
	}

	/**
	 * Return skill id
	 *
	 * @return Integer
	 */
	public function getSkillId()
	{
		if(!isset($this->skillId) || $this->skillId === null)
		{
			$this->skillId = $this->getXml()->getElementsByTagName('SkillID')->item(0)->nodeValue;
		}
		return $this->skillId;
	}

	/**
	 * Return old level
	 *
	 * @return Integer
	 */
	public function getOldLevel()
	{
		if(!isset($this->oldLevel) || $this->oldLevel === null)
		{
			$this->oldLevel = $this->getXml()->getElementsByTagName('OldLevel')->item(0)->nodeValue;
		}
		return $this->oldLevel;
	}

	/**
	 * Return new level
	 *
	 * @return Integer
	 */
	public function getNewLevel()
	{
		if(!isset($this->newLevel) || $this->newLevel === null)
		{
			$this->newLevel = $this->getXml()->getElementsByTagName('NewLevel')->item(0)->nodeValue;
		}
		return $this->newLevel;
	}

	/**
	 * Return season number
	 *
	 * @return Integer
	 */
	public function getSeason()
	{
		if(!isset($this->season) || $this->season === null)
		{
			$this->season = $this->getXml()->getElementsByTagName('Season')->item(0)->nodeValue;
		}
		return $this->season;
	}

	/**
	 * Return match round
	 *
	 * @return Integer
	 */
	public function getMatchRound()
	{
		if(!isset($this->matchRound) || $this->matchRound === null)
		{
			$this->matchRound = $this->getXml()->getElementsByTagName('MatchRound')->item(0)->nodeValue;
		}
		return $this->matchRound;
	}

	/**
	 * Return day number
	 *
	 * @return Integer
	 */
	public function getDay()
	{
		if(!isset($this->day) || $this->day === null)
		{
			$this->day = $this->getXml()->getElementsByTagName('DayNumber')->item(0)->nodeValue;
		}
		return $this->day;
	}
}
class HTPlayerEvents extends HTGlobal
{
	private $playerId = null;
	private $eventsNumber = null;
	private $eventsAvailable = null;
	private $events = null;

	/**
	 * Is events available for this player ?
	 *
	 * @return Boolean
	 */
	public function isEventsAvailable()
	{
		if(!isset($this->eventsAvailable) || $this->eventsAvailable === null)
		{
			$this->eventsAvailable = strtolower($this->getXml()->getElementsByTagName('PlayerEvents')->item(0)->getAttribute('Available')) == 'true';
		}
		return $this->eventsAvailable;
	}

	/**
	 * Return player id
	 *
	 * @return Integer
	 */
	public function getPlayerId()
	{
		if(!isset($this->playerId) || $this->playerId === null)
		{
			$this->playerId = $this->getXml()->getElementsByTagName('PlayerID')->item(0)->nodeValue;
		}
		return $this->playerId;
	}

	/**
	 * Return number of training events
	 *
	 * @return Integer
	 */
	public function getEventsNumber()
	{
		if($this->isEventsAvailable())
		{
			if(!isset($this->eventsNumber) || $this->eventsNumber === null)
			{
				$this->eventsNumber = $this->getXml()->getElementsByTagName('PlayerEvent')->length;
			}
			return $this->eventsNumber;
		}
		return null;
	}

	/**
	 * Return HTPlayerEvent object
	 *
	 * @param Integer $index
	 * @return HTTrainingEvent
	 */
	public function getEvent($index)
	{
		$index = round($index);
		if($index > 0 && $index <= $this->getEventsNumber())
		{
			--$index;
			if(!isset($this->events[$index]) || $this->events[$index] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//PlayerEvent');
				$event = new DOMDocument('1.0', 'UTF-8');
				$event->appendChild($event->importNode($nodeList->item($index), true));
				$this->events[$index] = new HTPlayerEvent($event);
			}
			return $this->events[$index];
		}
		return null;
	}
}
class HTPlayerEvent extends HTXml
{
	private $date = null;
	private $typeId = null;
	private $text = null;

	/**
	 * @param DOMDocument $xml
	 */
	public function __construct($xml)
	{
		$this->xmlText = $xml->saveXML();
		$this->xml = $xml;
	}

	/**
	 * Return event date
	 *
	 * @param String $format (php date() function format)
	 * @return String
	 */
	public function getDate($format = null)
	{
		if(!isset($this->date) || $this->date === null)
		{
			$this->date = $this->getXml()->getElementsByTagName('EventDate')->item(0)->nodeValue;
			if($format !== null)
			{
				$this->date = HTFunction::convertDate($this->date, $format);
			}
		}
		return $this->date;
	}

	/**
	 * Return event type id
	 *
	 * @return Integer
	 */
	public function getType()
	{
		if(!isset($this->typeId) || $this->typeId === null)
		{
			$this->typeId = $this->getXml()->getElementsByTagName('PlayerEventTypeID')->item(0)->nodeValue;
		}
		return $this->typeId;
	}

	/**
	 * Return event text
	 *
	 * @return String
	 */
	public function getText()
	{
		if(!isset($this->text) || $this->text === null)
		{
			$this->text = $this->getXml()->getElementsByTagName('EventText')->item(0)->nodeValue;
		}
		return $this->text;
	}
}
class HTAllianceCommon extends HTGlobal
{
	protected $id = null;
	protected $name = null;

	/**
	 * Return alliance id
	 *
	 * @return Integer
	 */
	public function getId()
	{
		if(!isset($this->id) || $this->id === null)
		{
			$this->id = $this->getXml()->getElementsByTagName('AllianceID')->item(0)->nodeValue;
		}
		return $this->id;
	}

	/**
	 * Return alliance name
	 *
	 * @return String
	 */
	public function getName()
	{
		if(!isset($this->name) || $this->name === null)
		{
			$this->name = $this->getXml()->getElementsByTagName('AllianceName')->item(0)->nodeValue;
		}
		return $this->name;
	}
}
class HTAllianceDetails extends HTAllianceCommon
{
	private $abbreviation = null;
	private $description = null;
	private $logoUrl = null;
	private $topRole = null;
	private $topUserId = null;
	private $topLoginName = null;
	private $creationDate = null;
	private $homepageUrl = null;
	private $numberMembers = null;
	private $numberLanguages = null;
	private $languages = null;
	private $message = null;
	private $numberUsersLogged = null;
	private $usersLogged = null;
	private $rules = null;
	private $roleId = null;
	private $roleName = null;
	private $awaitingRequests = null;
	private $hasRightToAcceptRequests = null;
	private $hasRightToSendNewsLetter = null;
	private $hasRightToCreatePolls = null;
	private $hasRightToEditPublicProperties = null;
	private $hasRightToExcludeMembers = null;
	private $hasRightToCreateRoles = null;
	private $hasRightToEditRoles = null;
	private $hasRightToModerate = null;
	private $hasRightToEditRules = null;
	private $dissolutionEndDate = null;
	const UNAVAILABLE = 'unavailable';

	/**
	 * Return alliance abbreviation
	 *
	 * @return String
	 */
	public function getAbbreviation()
	{
		if(!isset($this->abbreviation) || $this->abbreviation === null)
		{
			$node = $this->getXml()->getElementsByTagName('Abbreviation');
			if($node !== null && $node->length)
			{
				$this->abbreviation = $node->item(0)->nodeValue;
			}
			else
			{
				$this->abbreviation = self::UNAVAILABLE ;
			}
		}
		if($this->abbreviation === self::UNAVAILABLE )
		{
			return null;
		}
		return $this->abbreviation;
	}

	/**
	 * Return alliance description
	 *
	 * @return String
	 */
	public function getDescription()
	{
		if(!isset($this->description) || $this->description === null)
		{
			$this->description = $this->getXml()->getElementsByTagName('Description')->item(0)->nodeValue;
		}
		return $this->description;
	}

	/**
	 * Return alliance top role name
	 *
	 * @return String
	 */
	public function getTopRole()
	{
		if(!isset($this->topRole) || $this->topRole === null)
		{
			$this->topRole = $this->getXml()->getElementsByTagName('TopRole')->item(0)->nodeValue;
		}
		return $this->topRole;
	}

	/**
	 * Return alliance top user id
	 *
	 * @return Integer
	 */
	public function getTopUserId()
	{
		if(!isset($this->topUserId) || $this->topUserId === null)
		{
			$this->topUserId = $this->getXml()->getElementsByTagName('TopUserID')->item(0)->nodeValue;
		}
		return $this->topUserId;
	}

	/**
	 * Return alliance top user loginname
	 *
	 * @return String
	 */
	public function getTopLoginName()
	{
		if(!isset($this->topLoginName) || $this->topLoginName === null)
		{
			$this->topLoginName = $this->getXml()->getElementsByTagName('TopLoginname')->item(0)->nodeValue;
		}
		return $this->topLoginName;
	}

	/**
	 * Return alliance creation date
	 *
	 * @param String $format (php date() function format)
	 * @return String
	 */
	public function getCreationDate($format = null)
	{
		if(!isset($this->creationDate) || $this->creationDate === null)
		{
			$this->creationDate = $this->getXml()->getElementsByTagName('CreationDate')->item(0)->nodeValue;
			if($format !== null)
			{
				$this->creationDate = HTFunction::convertDate($this->creationDate, $format);
			}
		}
		return $this->creationDate;
	}

	/**
	 * Return alliance homepage url
	 *
	 * @return String
	 */
	public function getHomepageUrl()
	{
		if(!isset($this->homepageUrl) || $this->homepageUrl === null)
		{
			$node = $this->getXml()->getElementsByTagName('HomePageURL');
			if($node !== null && $node->length)
			{
				$this->homepageUrl = $node->item(0)->nodeValue;
			}
			else
			{
				$this->homepageUrl = self::UNAVAILABLE;
			}
		}
		if($this->homepageUrl === self::UNAVAILABLE )
		{
			return null;
		}
		return $this->homepageUrl;
	}

	/**
	 * Return alliance message
	 *
	 * @return String
	 */
	public function getMessage()
	{
		if(!isset($this->message) || $this->message === null)
		{
			$node = $this->getXml()->getElementsByTagName('Message');
			if($node !== null && $node->length)
			{
				$this->message = $node->item(0)->nodeValue;
			}
			else
			{
				$this->message = self::UNAVAILABLE;
			}
		}
		if($this->message === self::UNAVAILABLE )
		{
			return null;
		}
		return $this->message;
	}

	/**
	 * Return alliance logo url
	 *
	 * @return String
	 */
	public function getLogoUrl()
	{
		if(!isset($this->logoUrl) || $this->logoUrl === null)
		{
			$node = $this->getXml()->getElementsByTagName('LogoURL');
			if($node !== null && $node->length)
			{
				$this->logoUrl = $node->item(0)->nodeValue;
			}
			else
			{
				$this->logoUrl = self::UNAVAILABLE;
			}
		}
		if($this->logoUrl === self::UNAVAILABLE )
		{
			return null;
		}
		return $this->logoUrl;
	}

	/**
	 * Return alliance members number
	 *
	 * @return Integer
	 */
	public function getNumberMembers()
	{
		if(!isset($this->numberMembers) || $this->numberMembers === null)
		{
			$this->numberMembers = $this->getXml()->getElementsByTagName('NumberOfMembers')->item(0)->nodeValue;
		}
		return $this->numberMembers;
	}

	/**
	 * Return alliance languages number
	 *
	 * @return Integer
	 */
	public function getNumberLanguages()
	{
		if(!isset($this->numberLanguages) || $this->numberLanguages === null)
		{
			$this->numberLanguages = $this->getXml()->getElementsByTagName('Language')->length;
		}
		return $this->numberLanguages;
	}

	/**
	 * Return HTWorldLanguage object
	 *
	 * @param Integer $index
	 * @return HTWorldLanguage
	 */
	public function getLanguage($index)
	{
		$index = round($index);
		if($index > 0 && $index <= $this->getNumberLanguages())
		{
			--$index;
			if(!isset($this->languages[$index]) || $this->languages[$index] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//Language');
				$lang = new DOMDocument('1.0', 'UTF-8');
				$lang->appendChild($lang->importNode($nodeList->item($index), true));
				$this->languages[$index] = new HTWorldLanguage($lang);
			}
			return $this->languages[$index];
		}
		return null;
	}

	/**
	 * Return alliance languages number
	 *
	 * @return Integer
	 */
	public function getNumberUsersLogged()
	{
		if(!isset($this->numberUsersLogged) || $this->numberUsersLogged === null)
		{
			$this->numberUsersLogged = $this->getXml()->getElementsByTagName('LoggedInUsers')->item(0)->getAttribute('Count');
		}
		return $this->numberUsersLogged;
	}

	/**
	 * Return HTWorldLanguage object
	 *
	 * @param Integer $index
	 * @return HTWorldLanguage
	 */
	public function getUserLogged($index)
	{
		$index = round($index);
		if($index > 0 && $index <= $this->getNumberUsersLogged())
		{
			--$index;
			if(!isset($this->usersLogged[$index]) || $this->usersLogged[$index] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//LoggedInUser');
				$user = new DOMDocument('1.0', 'UTF-8');
				$user->appendChild($user->importNode($nodeList->item($index), true));
				$this->usersLogged[$index] = new HTUser($user);
			}
			return $this->usersLogged[$index];
		}
		return null;
	}

	/**
	 * Return alliance rules
	 *
	 * @return String
	 */
	public function getRules()
	{
		if(!isset($this->rules) || $this->rules === null)
		{
			$this->rules = $this->getXml()->getElementsByTagName('Rules')->item(0)->nodeValue;
		}
		return $this->rules;
	}

	/**
	 * Return user role id
	 *
	 * @return Integer
	 */
	public function getRoleId()
	{
		if(!isset($this->roleId) || $this->roleId === null)
		{
			$this->roleId = $this->getXml()->getElementsByTagName('RoleID')->item(0)->nodeValue;
		}
		return $this->roleId;
	}

	/**
	 * Return user role name
	 *
	 * @return String
	 */
	public function getRoleName()
	{
		if(!isset($this->roleName) || $this->roleName === null)
		{
			$this->roleName = $this->getXml()->getElementsByTagName('RoleName')->item(0)->nodeValue;
		}
		return $this->roleName;
	}

	/**
	 * Return number of awaiting requests
	 *
	 * @return Integer
	 */
	public function getNumberAwaitingRequests()
	{
		if(!isset($this->awaitingRequests) || $this->awaitingRequests === null)
		{
			$node = $this->getXml()->getElementsByTagName('AwaitingRequests');
			if($node !== null && $node->length)
			{
				$this->awaitingRequests = $node->item(0)->nodeValue;
			}
			else
			{
				$this->awaitingRequests = self::UNAVAILABLE;
			}
		}
		if($this->awaitingRequests === self::UNAVAILABLE )
		{
			return null;
		}
		return $this->awaitingRequests;
	}

	/**
	 * Does user have right to accept requests ?
	 *
	 * @return Boolean
	 */
	public function userHasRightToAcceptRequests()
	{
		if(!isset($this->hasRightToAcceptRequests) || $this->hasRightToAcceptRequests === null)
		{
			$node = $this->getXml()->getElementsByTagName('HasRightToAcceptRequests');
			if($node !== null && $node->length)
			{
				$this->hasRightToAcceptRequests = strtolower($node->item(0)->nodeValue) == "true";
			}
			else
			{
				$this->hasRightToAcceptRequests = false;
			}
		}
		return $this->hasRightToAcceptRequests;
	}

	/**
	 * Does user have right to edit public properties ?
	 *
	 * @return Boolean
	 */
	public function userHasRightToEditPublicProperties()
	{
		if(!isset($this->hasRightToEditPublicProperties) || $this->hasRightToEditPublicProperties === null)
		{
			$node = $this->getXml()->getElementsByTagName('HasRightToEditPublicProperties');
			if($node !== null && $node->length)
			{
				$this->hasRightToEditPublicProperties = strtolower($node->item(0)->nodeValue) == "true";
			}
			else
			{
				$this->hasRightToEditPublicProperties = false;
			}
		}
		return $this->hasRightToEditPublicProperties;
	}

	/**
	 * Does user have right to exclude members ?
	 *
	 * @return Boolean
	 */
	public function userHasRightToExcludeMembers()
	{
		if(!isset($this->hasRightToExcludeMembers) || $this->hasRightToExcludeMembers === null)
		{
			$node = $this->getXml()->getElementsByTagName('HasRightToExcludeMembers');
			if($node !== null && $node->length)
			{
				$this->hasRightToExcludeMembers = strtolower($node->item(0)->nodeValue) == "true";
			}
			else
			{
				$this->hasRightToExcludeMembers = false;
			}
		}
		return $this->hasRightToExcludeMembers;
	}

	/**
	 * Does user have right to create roles ?
	 *
	 * @return Boolean
	 */
	public function userHasRightToCreateRoles()
	{
		if(!isset($this->hasRightToCreateRoles) || $this->hasRightToCreateRoles === null)
		{
			$node = $this->getXml()->getElementsByTagName('HasRightToCreateRoles');
			if($node !== null && $node->length)
			{
				$this->hasRightToCreateRoles = strtolower($node->item(0)->nodeValue) == "true";
			}
			else
			{
				$this->hasRightToCreateRoles = false;
			}
		}
		return $this->hasRightToCreateRoles;
	}

	/**
	 * Does user have right to edit roles ?
	 *
	 * @return Boolean
	 */
	public function userHasRightToEditRoles()
	{
		if(!isset($this->hasRightToEditRoles) || $this->hasRightToEditRoles === null)
		{
			$node = $this->getXml()->getElementsByTagName('HasRightToEditRoles');
			if($node !== null && $node->length)
			{
				$this->hasRightToEditRoles = strtolower($node->item(0)->nodeValue) == "true";
			}
			else
			{
				$this->hasRightToEditRoles = false;
			}
		}
		return $this->hasRightToEditRoles;
	}

	/**
	 * Does user have right to moderate ?
	 *
	 * @return Boolean
	 */
	public function userHasRightToModerate()
	{
		if(!isset($this->hasRightToModerate) || $this->hasRightToModerate === null)
		{
			$node = $this->getXml()->getElementsByTagName('HasRightToModerate');
			if($node !== null && $node->length)
			{
				$this->hasRightToModerate = strtolower($node->item(0)->nodeValue) == "true";
			}
			else
			{
				$this->hasRightToModerate = false;
			}
		}
		return $this->hasRightToModerate;
	}

	/**
	 * Does user have right to send newsletter ?
	 *
	 * @return Boolean
	 */
	public function userHasRightToSendNewsLetter()
	{
		if(!isset($this->hasRightToSendNewsLetter) || $this->hasRightToSendNewsLetter === null)
		{
			$node = $this->getXml()->getElementsByTagName('HasRightToSendNewsLetter');
			if($node !== null && $node->length)
			{
				$this->hasRightToSendNewsLetter = strtolower($node->item(0)->nodeValue) == "true";
			}
			else
			{
				$this->hasRightToSendNewsLetter = false;
			}
		}
		return $this->hasRightToSendNewsLetter;
	}

	/**
	 * Does user have right to create polls ?
	 *
	 * @return Boolean
	 */
	public function userHasRightToCreatePolls()
	{
		if(!isset($this->hasRightToCreatePolls) || $this->hasRightToCreatePolls === null)
		{
			$node = $this->getXml()->getElementsByTagName('HasRightToCreatePolls');
			if($node !== null && $node->length)
			{
				$this->hasRightToCreatePolls = strtolower($node->item(0)->nodeValue) == "true";
			}
			else
			{
				$this->hasRightToCreatePolls = false;
			}
		}
		return $this->hasRightToCreatePolls;
	}

	/**
	 * Does user have right to edit rules ?
	 *
	 * @return Boolean
	 */
	public function userHasRightToEditRules()
	{
		if(!isset($this->hasRightToEditRules) || $this->hasRightToEditRules === null)
		{
			$node = $this->getXml()->getElementsByTagName('HasRightToEditRules');
			if($node !== null && $node->length)
			{
				$this->hasRightToEditRules = strtolower($node->item(0)->nodeValue) == "true";
			}
			else
			{
				$this->hasRightToEditRules = false;
			}
		}
		return $this->hasRightToEditRules;
	}

	/**
	 * Return dissolution date
	 *
	 * @param String $format (php date() function format)
	 * @return String
	 */
	public function getDissolutionDate($format = null)
	{
		if(!isset($this->dissolutionEndDate) || $this->dissolutionEndDate === null)
		{
			$node = $this->getXml()->getElementsByTagName('DissolutionEndDate');
			if($node !== null && $node->length)
			{
				$this->dissolutionEndDate = $node->item(0)->nodeValue;
				if($format !== null)
				{
					$this->dissolutionEndDate = HTFunction::convertDate($this->dissolutionEndDate, $format);
				}
			}
			else
			{
				$this->dissolutionEndDate = self::UNAVAILABLE ;
			}
		}
		if($this->dissolutionEndDate === self::UNAVAILABLE )
		{
			return null;
		}
		return $this->dissolutionEndDate;
	}
}
class HTUser extends HTXml
{
	protected $id = null;
	protected $loginName = null;

	/**
	 * @param DOMDocument $xml
	 */
	public function __construct($xml)
	{
		$this->xmlText = $xml->saveXML();
		$this->xml = $xml;
	}

	/**
	 * Return user id
	 *
	 * @return Integer
	 */
	public function getId()
	{
		if(!isset($this->id) || $this->id === null)
		{
			$this->id = $this->getXml()->getElementsByTagName('UserID')->item(0)->nodeValue;
		}
		return $this->id;
	}

	/**
	 * Return user alias
	 *
	 * @return String
	 */
	public function getAlias()
	{
		if(!isset($this->loginName) || $this->loginName === null)
		{
			$this->loginName = $this->getXml()->getElementsByTagName('Loginname')->item(0)->nodeValue;
		}
		return $this->loginName;
	}
}
class HTAllianceMembers extends HTAllianceCommon
{
	private $numberMembers = null;
	private $members = null;

	/**
	 * Return number of members
	 *
	 * @return Integer
	 */
	public function getNumberMembers()
	{
		if(!isset($this->numberMembers) || $this->numberMembers === null)
		{
			$this->numberMembers = $this->getXml()->getElementsByTagName('Member')->length;
		}
		return $this->numberMembers;
	}

	/**
	 * Return HTAllianceMember object
	 *
	 * @param Integer $index
	 * @return HTAllianceMember
	 */
	public function getMember($index)
	{
		$index = round($index);
		if($index > 0 && $index <= $this->getNumberMembers())
		{
			--$index;
			if(!isset($this->members[$index]) || $this->members[$index] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//Member');
				$member = new DOMDocument('1.0', 'UTF-8');
				$member->appendChild($member->importNode($nodeList->item($index), true));
				$this->members[$index] = new HTAllianceMember($member);
			}
			return $this->members[$index];
		}
		return null;
	}
}
class HTAllianceMember extends HTUser
{
	private $roleId = null;
	private $roleName = null;
	private $shipDate = null;

	/**
	 * Return user role id
	 *
	 * @return String
	 */
	public function getRoleId()
	{
		if(!isset($this->roleId) || $this->roleId === null)
		{
			$this->roleId = $this->getXml()->getElementsByTagName('RoleID')->item(0)->nodeValue;
		}
		return $this->roleId;
	}

	/**
	 * Return user role name
	 *
	 * @return String
	 */
	public function getRoleName()
	{
		if(!isset($this->roleName) || $this->roleName === null)
		{
			$this->roleName = $this->getXml()->getElementsByTagName('RoleName')->item(0)->nodeValue;
		}
		return $this->roleName;
	}

	/**
	 * Return user ship date
	 *
	 * @param String $format (php date() function format)
	 * @return String
	 */
	public function getShipDate($format = null)
	{
		if(!isset($this->shipDate) || $this->shipDate === null)
		{
			$this->shipDate = $this->getXml()->getElementsByTagName('MemberShipDate')->item(0)->nodeValue;
			if($format !== null)
			{
				$this->shipDate = HTFunction::convertDate($this->shipDate, $format);
			}
		}
		return $this->shipDate;
	}
}
class HTAllianceRoles extends HTAllianceCommon
{
	private $numberRoles = null;
	private $roles = null;

	/**
	 * Return number of roles
	 *
	 * @return Integer
	 */
	public function getNumberRoles()
	{
		if(!isset($this->numberRoles) || $this->numberRoles === null)
		{
			$this->numberRoles = $this->getXml()->getElementsByTagName('Role')->length;
		}
		return $this->numberRoles;
	}

	/**
	 * Return HTAllianceRole object
	 *
	 * @param Integer $index
	 * @return HTAllianceRole
	 */
	public function getRole($index)
	{
		$index = round($index);
		if($index > 0 && $index <= $this->getNumberRoles())
		{
			--$index;
			if(!isset($this->roles[$index]) || $this->roles[$index] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//Role');
				$role = new DOMDocument('1.0', 'UTF-8');
				$role->appendChild($role->importNode($nodeList->item($index), true));
				$this->roles[$index] = new HTAllianceRole($role);
			}
			return $this->roles[$index];
		}
		return null;
	}
}
class HTAllianceRole extends HTXml
{
	private $id = null;
	private $name = null;
	private $rank = null;
	private $memberCount = null;

	/**
	 * Return role id
	 *
	 * @return Integer
	 */
	public function getId()
	{
		if(!isset($this->id) || $this->id === null)
		{
			$this->id = $this->getXml()->getElementsByTagName('RoleID')->item(0)->nodeValue;
		}
		return $this->id;
	}

	/**
	 * Return role name
	 *
	 * @return String
	 */
	public function getName()
	{
		if(!isset($this->name) || $this->name === null)
		{
			$this->name = $this->getXml()->getElementsByTagName('RoleName')->item(0)->nodeValue;
		}
		return $this->name;
	}

	/**
	 * Return role rank
	 *
	 * @return Integer
	 */
	public function getRank()
	{
		if(!isset($this->rank) || $this->rank === null)
		{
			$this->rank = $this->getXml()->getElementsByTagName('RoleRank')->item(0)->nodeValue;
		}
		return $this->rank;
	}

	/**
	 * Return number of member
	 *
	 * @return Integer
	 */
	public function getNumberMember()
	{
		if(!isset($this->memberCount) || $this->memberCount === null)
		{
			$this->memberCount = $this->getXml()->getElementsByTagName('RoleMemberCount')->item(0)->nodeValue;
		}
		return $this->memberCount;
	}
}
class HTAllianceRoleDetails extends HTAllianceCommon
{
	private $roleId	= null;
	private $roleName	= null;
	private $maxMembers	= null;
	private $rank	= null;
	private $requestType	= null;
	private $description	= null;
	private $hasRightToAcceptRequests = null;
	private $hasRightToSendNewsLetter = null;
	private $hasRightToCreatePolls = null;
	private $hasRightToEditPublicProperties = null;
	private $hasRightToExcludeMembers = null;
	private $hasRightToCreateRoles = null;
	private $hasRightToEditRoles = null;
	private $hasRightToModerate = null;
	private $hasRightToEditRules = null;

	/**
	 * Return role id
	 *
	 * @return Integer
	 */
	public function getRoleId()
	{
		if(!isset($this->roleId) || $this->roleId === null)
		{
			$this->roleId = $this->getXml()->getElementsByTagName('RoleID')->item(0)->nodeValue;
		}
		return $this->roleId;
	}

	/**
	 * Return role name
	 *
	 * @return String
	 */
	public function getRoleName()
	{
		if(!isset($this->roleName) || $this->roleName === null)
		{
			$this->roleName = $this->getXml()->getElementsByTagName('RoleName')->item(0)->nodeValue;
		}
		return $this->roleName;
	}

	/**
	 * Return role rank
	 *
	 * @return Integer
	 */
	public function getRank()
	{
		if(!isset($this->rank) || $this->rank === null)
		{
			$this->rank = $this->getXml()->getElementsByTagName('RoleRank')->item(0)->nodeValue;
		}
		return $this->rank;
	}

	/**
	 * Return role max members
	 *
	 * @return Integer
	 */
	public function getMaxMembers()
	{
		if(!isset($this->maxMembers) || $this->maxMembers === null)
		{
			$this->maxMembers = $this->getXml()->getElementsByTagName('RoleMaxMembers')->item(0)->nodeValue;
		}
		return $this->maxMembers;
	}

	/**
	 * Return role request type
	 *
	 * @return Integer
	 */
	public function getRequestType()
	{
		if(!isset($this->requestType) || $this->requestType === null)
		{
			$this->requestType = $this->getXml()->getElementsByTagName('RoleRequestType')->item(0)->nodeValue;
		}
		return $this->requestType;
	}

	/**
	 * Return role description
	 *
	 * @return String
	 */
	public function getDescription()
	{
		if(!isset($this->description) || $this->description === null)
		{
			$this->description = $this->getXml()->getElementsByTagName('RoleDescription')->item(0)->nodeValue;
		}
		return $this->description;
	}

	/**
	 * Does role have right to accept requests ?
	 *
	 * @return Boolean
	 */
	public function hasRightToAcceptRequests()
	{
		if(!isset($this->hasRightToAcceptRequests) || $this->hasRightToAcceptRequests === null)
		{
			$node = $this->getXml()->getElementsByTagName('HasRightToAcceptRequests');
			if($node !== null && $node->length)
			{
				$this->hasRightToAcceptRequests = strtolower($node->item(0)->nodeValue) == "true";
			}
			else
			{
				$this->hasRightToAcceptRequests = false;
			}
		}
		return $this->hasRightToAcceptRequests;
	}

	/**
	 * Does role have right to edit public properties ?
	 *
	 * @return Boolean
	 */
	public function hasRightToEditPublicProperties()
	{
		if(!isset($this->hasRightToEditPublicProperties) || $this->hasRightToEditPublicProperties === null)
		{
			$node = $this->getXml()->getElementsByTagName('HasRightToEditPublicProperties');
			if($node !== null && $node->length)
			{
				$this->hasRightToEditPublicProperties = strtolower($node->item(0)->nodeValue) == "true";
			}
			else
			{
				$this->hasRightToEditPublicProperties = false;
			}
		}
		return $this->hasRightToEditPublicProperties;
	}

	/**
	 * Does role have right to exclude members ?
	 *
	 * @return Boolean
	 */
	public function hasRightToExcludeMembers()
	{
		if(!isset($this->hasRightToExcludeMembers) || $this->hasRightToExcludeMembers === null)
		{
			$node = $this->getXml()->getElementsByTagName('HasRightToExcludeMembers');
			if($node !== null && $node->length)
			{
				$this->hasRightToExcludeMembers = strtolower($node->item(0)->nodeValue) == "true";
			}
			else
			{
				$this->hasRightToExcludeMembers = false;
			}
		}
		return $this->hasRightToExcludeMembers;
	}

	/**
	 * Does role have right to create roles ?
	 *
	 * @return Boolean
	 */
	public function hasRightToCreateRoles()
	{
		if(!isset($this->hasRightToCreateRoles) || $this->hasRightToCreateRoles === null)
		{
			$node = $this->getXml()->getElementsByTagName('HasRightToCreateRoles');
			if($node !== null && $node->length)
			{
				$this->hasRightToCreateRoles = strtolower($node->item(0)->nodeValue) == "true";
			}
			else
			{
				$this->hasRightToCreateRoles = false;
			}
		}
		return $this->hasRightToCreateRoles;
	}

	/**
	 * Does role have right to edit roles ?
	 *
	 * @return Boolean
	 */
	public function hasRightToEditRoles()
	{
		if(!isset($this->hasRightToEditRoles) || $this->hasRightToEditRoles === null)
		{
			$node = $this->getXml()->getElementsByTagName('HasRightToEditRoles');
			if($node !== null && $node->length)
			{
				$this->hasRightToEditRoles = strtolower($node->item(0)->nodeValue) == "true";
			}
			else
			{
				$this->hasRightToEditRoles = false;
			}
		}
		return $this->hasRightToEditRoles;
	}

	/**
	 * Does role have right to moderate ?
	 *
	 * @return Boolean
	 */
	public function hasRightToModerate()
	{
		if(!isset($this->hasRightToModerate) || $this->hasRightToModerate === null)
		{
			$node = $this->getXml()->getElementsByTagName('HasRightToModerate');
			if($node !== null && $node->length)
			{
				$this->hasRightToModerate = strtolower($node->item(0)->nodeValue) == "true";
			}
			else
			{
				$this->hasRightToModerate = false;
			}
		}
		return $this->hasRightToModerate;
	}

	/**
	 * Does role have right to send newsletter ?
	 *
	 * @return Boolean
	 */
	public function hasRightToSendNewsLetter()
	{
		if(!isset($this->hasRightToSendNewsLetter) || $this->hasRightToSendNewsLetter === null)
		{
			$node = $this->getXml()->getElementsByTagName('HasRightToSendNewsLetter');
			if($node !== null && $node->length)
			{
				$this->hasRightToSendNewsLetter = strtolower($node->item(0)->nodeValue) == "true";
			}
			else
			{
				$this->hasRightToSendNewsLetter = false;
			}
		}
		return $this->hasRightToSendNewsLetter;
	}

	/**
	 * Does role have right to create polls ?
	 *
	 * @return Boolean
	 */
	public function hasRightToCreatePolls()
	{
		if(!isset($this->hasRightToCreatePolls) || $this->hasRightToCreatePolls === null)
		{
			$node = $this->getXml()->getElementsByTagName('HasRightToCreatePolls');
			if($node !== null && $node->length)
			{
				$this->hasRightToCreatePolls = strtolower($node->item(0)->nodeValue) == "true";
			}
			else
			{
				$this->hasRightToCreatePolls = false;
			}
		}
		return $this->hasRightToCreatePolls;
	}

	/**
	 * Does role have right to edit rules ?
	 *
	 * @return Boolean
	 */
	public function hasRightToEditRules()
	{
		if(!isset($this->hasRightToEditRules) || $this->hasRightToEditRules === null)
		{
			$node = $this->getXml()->getElementsByTagName('HasRightToEditRules');
			if($node !== null && $node->length)
			{
				$this->hasRightToEditRules = strtolower($node->item(0)->nodeValue) == "true";
			}
			else
			{
				$this->hasRightToEditRules = false;
			}
		}
		return $this->hasRightToEditRules;
	}
}
class HTFans extends HTGlobal
{
	private $mood = null;
	private $members = null;
	private $seasonExpectation = null;
	private $numberPlayedMatches = null;
	private $playedMatches = null;
	private $numberUpcomingMatches = null;
	private $upcomingMatches = null;

	/**
	 * Return fan mood
	 *
	 * @return Integer
	 */
	public function getMood()
	{
		if(!isset($this->mood) || $this->mood === null)
		{
			$this->mood = $this->getXml()->getElementsByTagName('FanMood')->item(0)->nodeValue;
		}
		return $this->mood;
	}

	/**
	 * Return fans number
	 *
	 * @return Integer
	 */
	public function getMembers()
	{
		if(!isset($this->members) || $this->members === null)
		{
			$this->members = $this->getXml()->getElementsByTagName('Members')->item(0)->nodeValue;
		}
		return $this->members;
	}

	/**
	 * Return number of played matches
	 *
	 * @return Integer
	 */
	public function getNumberPlayedMatches()
	{
		if(!isset($this->numberPlayedMatches) || $this->numberPlayedMatches === null)
		{
			$node = $this->getXml()->getElementsByTagName('PlayedMatches');
			$matchs = new DOMDocument('1.0', 'UTF-8');
			$matchs->appendChild($matchs->importNode($node->item(0), true));
			$this->numberPlayedMatches = $matchs->getElementsByTagName('Match')->length;
		}
		return $this->numberPlayedMatches;
	}

	/**
	 * Return HTFansPlayedMatch object
	 *
	 * @param Integer $index
	 * @return HTFansPlayedMatch
	 */
	public function getPlayedMatch($index)
	{
		$index = round($index);
		if($index > 0 && $index <= $this->getNumberPlayedMatches())
		{
			--$index;
			if(!isset($this->playedMatches[$index]) || $this->playedMatches[$index] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//PlayedMatches/Match');
				$node = new DOMDocument('1.0', 'UTF-8');
				$node->appendChild($node->importNode($nodeList->item($index), true));
				$this->playedMatches[$index] = new HTFansPlayedMatch($node);
			}
			return $this->playedMatches[$index];
		}
		return null;
	}

	/**
	 * Return number of upcoming matches
	 *
	 * @return Integer
	 */
	public function getNumberUpcomingMatches()
	{
		if(!isset($this->numberUpcomingMatches) || $this->numberUpcomingMatches === null)
		{
			$node = $this->getXml()->getElementsByTagName('UpcomingMatches');
			$matchs = new DOMDocument('1.0', 'UTF-8');
			$matchs->appendChild($matchs->importNode($node->item(0), true));
			$this->numberUpcomingMatches = $matchs->getElementsByTagName('Match')->length;
		}
		return $this->numberUpcomingMatches;
	}

	/**
	 * Return HTFansUpcomingMatch object
	 *
	 * @param Integer $index
	 * @return HTFansUpcomingMatch
	 */
	public function getUpcomingMatch($index)
	{
		$index = round($index);
		if($index > 0 && $index <= $this->getNumberUpcomingMatches())
		{
			--$index;
			if(!isset($this->upcomingMatches[$index]) || $this->upcomingMatches[$index] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//UpcomingMatches/Match');
				$node = new DOMDocument('1.0', 'UTF-8');
				$node->appendChild($node->importNode($nodeList->item($index), true));
				$this->upcomingMatches[$index] = new HTFansUpcomingMatch($node);
			}
			return $this->upcomingMatches[$index];
		}
		return null;
	}
}
class HTFansPlayedMatch extends HTFansUpcomingMatch
{
	private $homeGoals = null;
	private $awayGoals = null;
	private $fanMoodAfterMatch = null;
	private $weather = null;
	private $affluence = null;

	/**
	 * Return home goals number
	 *
	 * @return Integer
	 */
	public function getHomeGoals()
	{
		if(!isset($this->homeGoals) || $this->homeGoals === null)
		{
			$this->homeGoals = $this->getXml()->getElementsByTagName('HomeGoals')->item(0)->nodeValue;
		}
		return $this->homeGoals;
	}

	/**
	 * Return away goals number
	 *
	 * @return Integer
	 */
	public function getAwayGoals()
	{
		if(!isset($this->awayGoals) || $this->awayGoals === null)
		{
			$this->awayGoals = $this->getXml()->getElementsByTagName('AwayGoals')->item(0)->nodeValue;
		}
		return $this->awayGoals;
	}

	/**
	 * Return fan expectation
	 *
	 * @return Integer
	 */
	public function getFanMoodAfter()
	{
		if(!isset($this->fanMoodAfterMatch) || $this->fanMoodAfterMatch === null)
		{
			$this->fanMoodAfterMatch = $this->getXml()->getElementsByTagName('FanMoodAfterMatch')->item(0)->nodeValue;
		}
		return $this->fanMoodAfterMatch;
	}

	/**
	 * Return match weather
	 *
	 * @return Integer
	 */
	public function getWeather()
	{
		if(!isset($this->weather) || $this->weather === null)
		{
			$this->weather = $this->getXml()->getElementsByTagName('Weather')->item(0)->nodeValue;
		}
		return $this->weather;
	}

	/**
	 * Return match affluence
	 *
	 * @return Integer
	 */
	public function getAffluence()
	{
		if(!isset($this->affluence) || $this->affluence === null)
		{
			$this->affluence = $this->getXml()->getElementsByTagName('SoldSeats')->item(0)->nodeValue;
		}
		return $this->affluence;
	}

}
class HTFansUpcomingMatch extends HTXml
{
	private $matchId = null;
	private $homeTeamId = null;
	private $homeTeamName = null;
	private $awayTeamId = null;
	private $awayTeamName = null;
	private $matchDate = null;
	private $matchType = null;
	private $fanMatchExpectation = null;

	/**
	 * @param DOMDocument $xml
	 */
	public function __construct($xml)
	{
		$this->xmlText = $xml->saveXML();
		$this->xml = $xml;
	}

	/**
	 * Return match id
	 *
	 * @return Integer
	 */
	public function getId()
	{
		if(!isset($this->matchId) || $this->matchId === null)
		{
			$this->matchId = $this->getXml()->getElementsByTagName('MatchID')->item(0)->nodeValue;
		}
		return $this->matchId;
	}

	/**
	 * Return home team id
	 *
	 * @return Integer
	 */
	public function getHomeTeamId()
	{
		if(!isset($this->homeTeamId) || $this->homeTeamId === null)
		{
			$this->homeTeamId = $this->getXml()->getElementsByTagName('HomeTeamID')->item(0)->nodeValue;
		}
		return $this->homeTeamId;
	}

	/**
	 * Return home team name
	 *
	 * @return String
	 */
	public function getHomeTeamName()
	{
		if(!isset($this->homeTeamName) || $this->homeTeamName === null)
		{
			$this->homeTeamName = $this->getXml()->getElementsByTagName('HomeTeamName')->item(0)->nodeValue;
		}
		return $this->homeTeamName;
	}

	/**
	 * Return away team id
	 *
	 * @return Integer
	 */
	public function getAwayTeamId()
	{
		if(!isset($this->awayTeamId) || $this->awayTeamId === null)
		{
			$this->awayTeamId = $this->getXml()->getElementsByTagName('AwayTeamID')->item(0)->nodeValue;
		}
		return $this->awayTeamId;
	}

	/**
	 * Return away team name
	 *
	 * @return String
	 */
	public function getAwayTeamName()
	{
		if(!isset($this->awayTeamName) || $this->awayTeamName === null)
		{
			$this->awayTeamName = $this->getXml()->getElementsByTagName('AwayTeamName')->item(0)->nodeValue;
		}
		return $this->awayTeamName;
	}

	/**
	 * Return match date
	 *
	 * @param String $format (php date() function format)
	 * @return String
	 */
	public function getDate($format = null)
	{
		if(!isset($this->matchDate) || $this->matchDate === null)
		{
			$this->matchDate = $this->getXml()->getElementsByTagName('MatchDate')->item(0)->nodeValue;
			if($format !== null)
			{
				$this->matchDate = HTFunction::convertDate($this->matchDate, $format);
			}
		}
		return $this->matchDate;
	}

	/**
	 * Return match type
	 *
	 * @return Integer
	 */
	public function getType()
	{
		if(!isset($this->matchType) || $this->matchType === null)
		{
			$this->matchType = $this->getXml()->getElementsByTagName('MatchType')->item(0)->nodeValue;
		}
		return $this->matchType;
	}

	/**
	 * Return fan expectation
	 *
	 * @return Integer
	 */
	public function getFanExpectation()
	{
		if(!isset($this->fanMatchExpectation) || $this->fanMatchExpectation === null)
		{
			$this->fanMatchExpectation = $this->getXml()->getElementsByTagName('FanMatchExpectation')->item(0)->nodeValue;
		}
		return $this->fanMatchExpectation;
	}
}
class HTAchievements extends HTGlobal
{
	private $maxPoints = null;
	private $achievementsNumber = null;
	private $achievements = null;

	/**
	 * Return max points achievements
	 *
	 * @return Integer
	 */
	public function getMaxPoints()
	{
		if(!isset($this->maxPoints) || $this->maxPoints === null)
		{
			$this->maxPoints = $this->getXml()->getElementsByTagName('MaxPoints')->item(0)->nodeValue;
		}
		return $this->maxPoints;
	}

	/**
	 * Return achievements number
	 *
	 * @return Integer
	 */
	public function getNumberAchievements()
	{
		if(!isset($this->achievementsNumber) || $this->achievementsNumber === null)
		{
			$this->achievementsNumber = $this->getXml()->getElementsByTagName('Achievement')->length;
		}
		return $this->achievementsNumber;
	}

	/**
	 * Return HTAchievement object
	 *
	 * @param Integer $index
	 * @return HTAchievement
	 */
	public function getAchievement($index)
	{
		$index = round($index);
		if($index > 0 && $index <= $this->getNumberAchievements())
		{
			--$index;
			if(!isset($this->achievements[$index]) || $this->achievements[$index] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//Achievement');
				$achievement = new DOMDocument('1.0', 'UTF-8');
				$achievement->appendChild($achievement->importNode($nodeList->item($index), true));
				$this->achievements[$index] = new HTAchievement($achievement);
			}
			return $this->achievements[$index];
		}
		return null;
	}
}
class HTAchievement extends HTXml
{
	private $type = null;
	private $text = null;
	private $category = null;
	private $date = null;
	private $points = null;
	private $multiLevel = null;
	private $numberOfEvents = null;

	/**
	 * @param DOMDocument $xml
	 */
	public function __construct($xml)
	{
		$this->xmlText = $xml->saveXML();
		$this->xml = $xml;
	}

	/**
	 * Return achievement type
	 *
	 * @return Integer
	 */
	public function getType()
	{
		if(!isset($this->type) || $this->type === null)
		{
			$this->type = $this->getXml()->getElementsByTagName('AchievementTypeID')->item(0)->nodeValue;
		}
		return $this->type;
	}

	/**
	 * Return achievement text
	 *
	 * @return String
	 */
	public function getText()
	{
		if(!isset($this->text) || $this->text === null)
		{
			$this->text = $this->getXml()->getElementsByTagName('AchievementText')->item(0)->nodeValue;
		}
		return $this->text;
	}

	/**
	 * Return achievement category
	 *
	 * @return Integer
	 */
	public function getCategory()
	{
		if(!isset($this->category) || $this->category === null)
		{
			$this->category = $this->getXml()->getElementsByTagName('CategoryID')->item(0)->nodeValue;
		}
		return $this->category;
	}

	/**
	 * Return achievement date
	 *
	 * @param String $format (php date() function format)
	 * @return String
	 */
	public function getDate($format = null)
	{
		if(!isset($this->date) || $this->date === null)
		{
			$this->date = $this->getXml()->getElementsByTagName('EventDate')->item(0)->nodeValue;
			if($format !== null)
			{
				$this->date = HTFunction::convertDate($this->date, $format);
			}
		}
		return $this->date;
	}

	/**
	 * Return achievement points
	 *
	 * @return Integer
	 */
	public function getPoints()
	{
		if(!isset($this->points) || $this->points === null)
		{
			$this->points = $this->getXml()->getElementsByTagName('Points')->item(0)->nodeValue;
		}
		return $this->points;
	}

	/**
	 * Return if achievement is multi level
	 *
	 * @return Boolean
	 */
	public function isMultiLevel()
	{
		if(!isset($this->multiLevel) || $this->multiLevel === null)
		{
			$this->multiLevel = strtolower($this->getXml()->getElementsByTagName('MultiLevel')->item(0)->nodeValue) == "true";
		}
		return $this->multiLevel;
	}

	/**
	 * Return achievement number of events
	 *
	 * @return Integer
	 */
	public function getNumberOfEvents()
	{
		if(!isset($this->numberOfEvents) || $this->numberOfEvents === null)
		{
			$this->numberOfEvents = $this->getXml()->getElementsByTagName('NumberOfEvents')->item(0)->nodeValue;
		}
		return $this->numberOfEvents;
	}
}
class HTNationalPlayersStats extends HTCommonTeam
{
	private $matchType = null;
	private $moreExist = null;
	private $playerNumber = null;
	private $players = null;

	/**
	 * Return match type (NT for national team match, WC for worldcup match)
	 *
	 * @return String
	 */
	public function getMatchType()
	{
		if(!isset($this->matchType) || $this->matchType === null)
		{
			$this->matchType = $this->getXml()->getElementsByTagName('MatchTypeCategory')->item(0)->nodeValue;
		}
		return $this->matchType;
	}

	/**
	 * Return if more records available
	 *
	 * @return Integer
	 */
	public function hasMoreRecords()
	{
		if(!isset($this->moreExist) || $this->moreExist === null)
		{
			$this->moreExist = strtolower($this->getXml()->getElementsByTagName('MoreRecordsAvailable')->item(0)->nodeValue) == "true";
		}
		return $this->moreExist;
	}

	/**
	 * Return players number
	 *
	 * @return Integer
	 */
	public function getNumberPlayers()
	{
		if(!isset($this->playerNumber) || $this->playerNumber === null)
		{
			$this->playerNumber = $this->getXml()->getElementsByTagName('Player')->length;
		}
		return $this->playerNumber;
	}

	/**
	 * Return HTAchievement object
	 *
	 * @param Integer $index
	 * @return HTNationalPlayerStats
	 */
	public function getPlayer($index)
	{
		$index = round($index);
		if($index > 0 && $index <= $this->getNumberPlayers())
		{
			--$index;
			if(!isset($this->players[$index]) || $this->players[$index] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//Achievement');
				$player = new DOMDocument('1.0', 'UTF-8');
				$player->appendChild($player->importNode($nodeList->item($index), true));
				$this->players[$index] = new HTNationalPlayerStats($player);
			}
			return $this->players[$index];
		}
		return null;
	}
}
class HTNationalPlayerStats extends HTXml
{
	private $id = null;
	private $name = null;
	private $nbMatches = null;

	/**
	 * Create an instance
	 *
	 * @param DOMDocument $xml
	 */
	public function __construct($xml)
	{
		$this->xmlText = $xml->saveXML();
		$this->xml = $xml;
	}

	/**
	 * Return player id
	 *
	 * @return Integer
	 */
	public function getId()
	{
		if(!isset($this->id) || $this->id === null)
		{
			$this->id = $this->getXml()->getElementsByTagName('PlayerID')->item(0)->nodeValue;
		}
		return $this->id;
	}

	/**
	 * Return player name
	 *
	 * @return String
	 */
	public function getName()
	{
		if(!isset($this->name) || $this->name === null)
		{
			$this->name = $this->getXml()->getElementsByTagName('PlayerName')->item(0)->nodeValue;
		}
		return $this->name;
	}

	/**
	 * Return number of matches
	 *
	 * @return Integer
	 */
	public function getNumberOfMatches()
	{
		if(!isset($this->nbMatches) || $this->nbMatches === null)
		{
			$this->nbMatches = $this->getXml()->getElementsByTagName('NrOfMatches')->item(0)->nodeValue;
		}
		return $this->nbMatches;
	}
}
class HTFlags
{
	private $flagArray = null;
	private $flagNamedArray = null;

	/**
	 * Create instance
	 *
	 * @param Array $flagArray
	 */
	public function __construct($flagArray)
	{
		$this->flagArray = array_values($flagArray);
		$this->flagNamedArray = $flagArray;
	}

	/**
	 * Return flag number
	 *
	 * @return Integer
	 */
	public function getFlagNumber()
	{
		return count($this->flagArray);
	}

	/**
	 * Return HTWorldLeague object
	 *
	 * @param Integer $index
	 * @return HTWorldLeague
	 */
	public function getLeagueOfFlagByNumber($index)
	{
		$index = round($index);
		if($index > 0 && $index <= $this->getFlagNumber())
		{
			return $this->flagArray[--$index];
		}
		return null;
	}

	/**
	 * Return HTWorldLeague object
	 *
	 * @param String $countryName
	 * @return HTWorldLeague
	 */
	public function getLeagueOfFlagByName($countryName)
	{
		if(key_exists($countryName, $this->flagNamedArray))
		{
			return $this->flagNamedArray[$countryName];
		}
		return null;
	}
}
class HTWorldCupMatches extends HTGlobal
{
	private $cupId = null;
	private $season = null;
	private $matchRound = null;
	private $cupSeriesUnitID = null;
	private $matchNumber = null;
	private $matches = null;
	private $roundNumber = null;
	private $rounds = null;

	/**
	 * Return cup id
	 *
	 * @return Integer
	 */
	public function getCupId()
	{
		if(!isset($this->cupId) || $this->cupId === null)
		{
			$this->cupId = $this->getXml()->getElementsByTagName('CupID')->item(0)->nodeValue;
		}
		return $this->cupId;
	}

	/**
	 * Return season number
	 *
	 * @return Integer
	 */
	public function getSeason()
	{
		if(!isset($this->season) || $this->season === null)
		{
			$this->season = $this->getXml()->getElementsByTagName('Season')->item(0)->nodeValue;
		}
		return $this->season;
	}

	/**
	 * Return match round
	 *
	 * @return Integer
	 */
	public function getMatchRound()
	{
		if(!isset($this->matchRound) || $this->matchRound === null)
		{
			$this->matchRound = $this->getXml()->getElementsByTagName('MatchRound')->item(0)->nodeValue;
		}
		return $this->matchRound;
	}

	/**
	 * Return cup series unit id
	 *
	 * @return Integer
	 */
	public function getCupSeriesUnitId()
	{
		if(!isset($this->cupSeriesUnitID) || $this->cupSeriesUnitID === null)
		{
			$this->cupSeriesUnitID = $this->getXml()->getElementsByTagName('CupSeriesUnitID')->item(0)->nodeValue;
		}
		return $this->cupSeriesUnitID;
	}

	/**
	 * Return match number
	 *
	 * @return Integer
	 */
	public function getMatchNumber()
	{
		if(!isset($this->matchNumber) || $this->matchNumber === null)
		{
			$this->matchNumber = $this->getXml()->getElementsByTagName('Match')->length;
		}
		return $this->matchNumber;
	}

	/**
	 * Return HTWorldCupMatch object
	 *
	 * @param Integer $index
	 * @return HTWorldCupMatch
	 */
	public function getMatch($index)
	{
		$index = round($index);
		if($index > 0 && $index <= $this->getMatchNumber())
		{
			--$index;
			if(!isset($this->matches[$index]) || $this->matches[$index] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//Match');
				$match = new DOMDocument('1.0', 'UTF-8');
				$match->appendChild($match->importNode($nodeList->item($index), true));
				$this->matches[$index] = new HTWorldCupMatch($match);
			}
			return $this->matches[$index];
		}
		return null;
	}

	/**
	 * Return round number
	 *
	 * @return Integer
	 */
	public function getRoundNumber()
	{
		if(!isset($this->roundNumber) || $this->roundNumber === null)
		{
			$this->roundNumber = $this->getXml()->getElementsByTagName('Round')->length;
		}
		return $this->roundNumber;
	}

	/**
	 * Return HTWorldCupRound object
	 *
	 * @param Integer $index
	 * @return HTWorldCupRound
	 */
	public function getRound($index)
	{
		$index = round($index);
		if($index > 0 && $index <= $this->getRoundNumber())
		{
			--$index;
			if(!isset($this->rounds[$index]) || $this->rounds[$index] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//Round');
				$round = new DOMDocument('1.0', 'UTF-8');
				$round->appendChild($round->importNode($nodeList->item($index), true));
				$this->rounds[$index] = new HTWorldCupRound($round);
			}
			return $this->rounds[$index];
		}
		return null;
	}
}
class HTWorldCupMatch extends HTXml
{
	private $id = null;
	private $homeTeamId = null;
	private $homeTeamName = null;
	private $awayTeamId = null;
	private $awayTeamName = null;
	private $startDate = null;
	private $endDate = null;
	private $homeGoals = null;
	private $awayGoals = null;

	/**
	 * Create an instance
	 *
	 * @param DOMDocument $xml
	 */
	public function __construct($xml)
	{
		$this->xmlText = $xml->saveXML();
		$this->xml = $xml;
	}

	/**
	 * Return match id
	 *
	 * @return Integer
	 */
	public function getMatchId()
	{
		if(!isset($this->id) || $this->id === null)
		{
			$this->id = $this->getXml()->getElementsByTagName('MatchID')->item(0)->nodeValue;
		}
		return $this->id;
	}

	/**
	 * Return home team id
	 *
	 * @return Integer
	 */
	public function getHomeTeamId()
	{
		if(!isset($this->homeTeamId) || $this->homeTeamId === null)
		{
			$xpath = new DOMXPath($this->getXml());
			$nodeList = $xpath->query("//HomeTeam/TeamID");
			$this->homeTeamId = $nodeList->item(0)->nodeValue;
		}
		return $this->homeTeamId;
	}

	/**
	 * Return home team name
	 *
	 * @return String
	 */
	public function getHomeTeamName()
	{
		if(!isset($this->homeTeamName) || $this->homeTeamName === null)
		{
			$xpath = new DOMXPath($this->getXml());
			$nodeList = $xpath->query("//HomeTeam/TeamName");
			$this->homeTeamName = $nodeList->item(0)->nodeValue;
		}
		return $this->homeTeamName;
	}

	/**
	 * Return away team id
	 *
	 * @return Integer
	 */
	public function getAwayTeamId()
	{
		if(!isset($this->awayTeamId) || $this->awayTeamId === null)
		{
			$xpath = new DOMXPath($this->getXml());
			$nodeList = $xpath->query("//AwayTeam/TeamID");
			$this->awayTeamId = $nodeList->item(0)->nodeValue;
		}
		return $this->awayTeamId;
	}

	/**
	 * Return away team name
	 *
	 * @return String
	 */
	public function getAwayTeamName()
	{
		if(!isset($this->awayTeamName) || $this->awayTeamName === null)
		{
			$xpath = new DOMXPath($this->getXml());
			$nodeList = $xpath->query("//AwayTeam/TeamName");
			$this->awayTeamName = $nodeList->item(0)->nodeValue;
		}
		return $this->awayTeamName;
	}

	/**
	 * Return match start date
	 *
	 * @param String $format (php date() function format)
	 * @return String
	 */
	public function getStartDate($format = null)
	{
		if(!isset($this->startDate) || $this->startDate === null)
		{
			$this->startDate = $this->getXml()->getElementsByTagName('MatchDate')->item(0)->nodeValue;
			if($format !== null)
			{
				$this->startDate = HTFunction::convertDate($this->startDate, $format);
			}
		}
		return $this->startDate;
	}

	/**
	 * Return match end date
	 *
	 * @param String $format (php date() function format)
	 * @return String
	 */
	public function getEndDate($format = null)
	{
		if(!isset($this->endDate) || $this->endDate === null)
		{
			$this->endDate = $this->getXml()->getElementsByTagName('FinishedDate')->item(0)->nodeValue;
			if($format !== null)
			{
				$this->endDate = HTFunction::convertDate($this->endDate, $format);
			}
		}
		return $this->endDate;
	}

	/**
	 * Return home team goals number
	 *
	 * @return Integer
	 */
	public function getHomeGoals()
	{
		if(!isset($this->homeGoals) || $this->homeGoals === null)
		{
			$this->homeGoals = $this->getXml()->getElementsByTagName('HomeGoals')->item(0)->nodeValue;
		}
		return $this->homeGoals;
	}

	/**
	 * Return away team goals number
	 *
	 * @return Integer
	 */
	public function getAwayGoals()
	{
		if(!isset($this->awayGoals) || $this->awayGoals === null)
		{
			$this->awayGoals = $this->getXml()->getElementsByTagName('AwayGoals')->item(0)->nodeValue;
		}
		return $this->awayGoals;
	}
}
class HTWorldCupRound extends HTXml
{
	private $matchRound = null;
	private $startDate = null;


	/**
	 * Create an instance
	 *
	 * @param DOMDocument $xml
	 */
	public function __construct($xml)
	{
		$this->xmlText = $xml->saveXML();
		$this->xml = $xml;
	}

	/**
	 * Return match round number
	 *
	 * @return Integer
	 */
	public function getMatchRound()
	{
		if(!isset($this->matchRound) || $this->matchRound === null)
		{
			$this->matchRound = $this->getXml()->getElementsByTagName('MatchRound')->item(0)->nodeValue;
		}
		return $this->matchRound;
	}

	/**
	 * Return match start date
	 *
	 * @param String $format (php date() function format)
	 * @return String
	 */
	public function getStartDate($format = null)
	{
		if(!isset($this->startDate) || $this->startDate === null)
		{
			$this->startDate = $this->getXml()->getElementsByTagName('StartDate')->item(0)->nodeValue;
			if($format !== null)
			{
				$this->startDate = HTFunction::convertDate($this->startDate, $format);
			}
		}
		return $this->startDate;
	}
}
class HTNationalMatches extends HTGlobal
{
	private $leagueOfficeTypeID = null;
	private $matchNumber = null;
	private $matches = null;

	/**
	 * Return league office type id
	 *
	 * @return Integer
	 */
	public function getLeagueOfficeTypeID()
	{
		if(!isset($this->leagueOfficeTypeID) || $this->leagueOfficeTypeID === null)
		{
			$this->leagueOfficeTypeID = $this->getXml()->getElementsByTagName('LeagueOfficeTypeID')->item(0)->nodeValue;
		}
		return $this->leagueOfficeTypeID;
	}

	/**
	 * Return match number
	 *
	 * @return Integer
	 */
	public function getMatchNumber()
	{
		if(!isset($this->matchNumber) || $this->matchNumber === null)
		{
			$this->matchNumber = $this->getXml()->getElementsByTagName('Match')->length;
		}
		return $this->matchNumber;
	}

	/**
	 * Return HTNationalMatch object
	 *
	 * @param Integer $index
	 * @return HTNationalMatch
	 */
	public function getMatch($index)
	{
		$index = round($index);
		if($index > 0 && $index <= $this->getMatchNumber())
		{
			--$index;
			if(!isset($this->matches[$index]) || $this->matches[$index] === null)
			{
				$xpath = new DOMXPath($this->getXml());
				$nodeList = $xpath->query('//Match');
				$match = new DOMDocument('1.0', 'UTF-8');
				$match->appendChild($match->importNode($nodeList->item($index), true));
				$this->matches[$index] = new HTNationalMatch($match);
			}
			return $this->rounds[$index];
		}
		return null;
	}
}
class HTNationalMatch extends HTXml
{
	private $id = null;
	private $date = null;
	private $type = null;
	private $homeTeamName = null;
	private $awayTeamName = null;
	private $homeGoals = null;
	private $awayGoals = null;
	private $resultAvailable = null;

	/**
	 * Create an instance
	 *
	 * @param DOMDocument $xml
	 */
	public function __construct($xml)
	{
		$this->xmlText = $xml->saveXML;
		$this->xml = $xml;
	}

	/**
	 * Return match id
	 *
	 * @return Integer
	 */
	public function getMatchId()
	{
		if(!isset($this->id) || $this->id === null)
		{
			$this->id = $this->getXml()->getElementsByTagName('MatchID')->item(0)->nodeValue;
		}
		return $this->id;
	}

	/**
	 * Return match date
	 *
	 * @param String $format (php date() function format)
	 * @return String
	 */
	public function getDate($format = null)
	{
		if(!isset($this->date) || $this->date === null)
		{
			$this->date = $this->getXml()->getElementsByTagName('MatchDate')->item(0)->nodeValue;
			if($format !== null)
			{
				$this->date = HTFunction::convertDate($this->date, $format);
			}
		}
		return $this->date;
	}

	/**
	 * Return match type
	 *
	 * @return Integer
	 */
	public function getTypeId()
	{
		if(!isset($this->type) || $this->type === null)
		{
			$this->type = $this->getXml()->getElementsByTagName('MatchType')->item(0)->nodeValue;
		}
		return $this->type;
	}

	/**
	 * Return home type name
	 *
	 * @return String
	 */
	public function getHomeTeamName()
	{
		if(!isset($this->homeTeamName) || $this->homeTeamName === null)
		{
			$this->homeTeamName = $this->getXml()->getElementsByTagName('HomeTeamName')->item(0)->nodeValue;
		}
		return $this->homeTeamName;
	}

	/**
	 * Return away type name
	 *
	 * @return String
	 */
	public function getAwayTeamName()
	{
		if(!isset($this->awayTeamName) || $this->awayTeamName === null)
		{
			$this->awayTeamName = $this->getXml()->getElementsByTagName('AwayTeamName')->item(0)->nodeValue;
		}
		return $this->awayTeamName;
	}

	/**
	 * Return if match is finished
	 *
	 * @return Boolean
	 */
	public function isResultAvailable()
	{
		if(!isset($this->resultAvailable) || $this->resultAvailable === null)
		{
			$this->resultAvailable = false;
			if($this->getXml()->getElementsByTagName('HomeGoals')->length && $this->getXml()->getElementsByTagName('AwayGoals')->length)
			{
				$this->resultAvailable = true;
			}
		}
		return $this->resultAvailable;
	}

	/**
	 * Return home goals number
	 *
	 * @return Integer
	 */
	public function getHomeGoals()
	{
		if($this->isResultAvailable())
		{
			if(!isset($this->homeGoals) || $this->homeGoals === null)
			{
				$this->homeGoals = $this->getXml()->getElementsByTagName('HomeGoals')->item(0)->nodeValue;
			}
			return $this->homeGoals;
		}
		return null;
	}

	/**
	 * Return away goals number
	 *
	 * @return Integer
	 */
	public function getAwayGoals()
	{
		if($this->isResultAvailable())
		{
			if(!isset($this->awayGoals) || $this->awayGoals === null)
			{
				$this->awayGoals = $this->getXml()->getElementsByTagName('AwayGoals')->item(0)->nodeValue;
			}
			return $this->awayGoals;
		}
		return null;
	}
}
class HTError extends Exception
{
	/**
	 * @var DOMDocument
	 */
	private $xml;
	private $isXml;

	/**
	 * @param String $message
	 * @param Boolean $isXml
	 */
	public function __construct($message, $isXml)
	{
		$this->isXml = $isXml;
		parent::__construct($message);
		if($this->isXmlError())
		{
			$this->xml = new DOMDocument('1.0', 'UTF-8');
			$this->xml->loadXML($message);
		}
	}

	/**
	 * Is error xml ?
	 *
	 * @return Boolean
	 */
	public function isXmlError()
	{
		return $this->isXml;
	}

	/**
	 * Return error code
	 *
	 * @return Integer
	 */
	public function getErrorCode()
	{
		if($this->isXmlError())
		{
			return $this->xml->getElementsByTagName('ErrorCode')->item(0)->nodeValue;
		}
		return null;
	}

	/**
	 * Return error
	 *
	 * @return String
	 */
	public function getError()
	{
		if($this->isXmlError())
		{
			return $this->xml->getElementsByTagName('Error')->item(0)->nodeValue;
		}
		return null;
	}

	/**
	 * Return error guid
	 *
	 * @return String
	 */
	public function getErrorGuid()
	{
		if($this->isXmlError())
		{
			return $this->xml->getElementsByTagName('ErrorGUID')->item(0)->nodeValue;
		}
		return null;
	}

	/**
	 * Return server
	 *
	 * @return String
	 */
	public function getServer()
	{
		if($this->isXmlError())
		{
			return $this->xml->getElementsByTagName('Server')->item(0)->nodeValue;
		}
		return null;
	}

	/**
	 * Return request
	 *
	 * @return String
	 */
	public function getRequest()
	{
		if($this->isXmlError())
		{
			return $this->xml->getElementsByTagName('Request')->item(0)->nodeValue;
		}
		return null;
	}

	/**
	 * Return xml
	 *
	 * @param Boolean $asObject
	 * @return DOMDocument | String
	 */
	public function getXml($asObject = true)
	{
		if($this->isXmlError())
		{
			if($asObject === true)
			{
				return $this->xml;
			}
			return $this->xml->saveXML();
		}
		else
		{
			return parent::getMessage();
		}
	}
}
/*-events-*/
class HTEvent20 extends HTEvent
{
	/**
	 * @return Integer
	 */
	public function getTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}
}
class HTEvent21 extends HTEvent
{
	/**
	 * @return Integer
	 */
	public function getTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}
}
class HTEvent25 extends HTEvent
{
	const VALUE1 = 'Derby';

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}
}
class HTEvent26 extends HTEvent
{
	const VALUE1 = 'Neutral ground';

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}
}
class HTEvent30 extends HTEvent
{
	const VALUE1 = 'Rain';

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return Integer
	 */
	public function getCrowdTurnout()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}
}
class HTEvent31 extends HTEvent
{
	const VALUE1 = 'Overcast';

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return Integer
	 */
	public function getCrowdTurnout()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}
}
class HTEvent32 extends HTEvent
{
	const VALUE1 = 'Partially cloudy';

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return Integer
	 */
	public function getCrowdTurnout()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}
}
class HTEvent33 extends HTEvent
{
	const VALUE1 = 'Sunny';

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return Integer
	 */
	public function getCrowdTurnout()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}
}
class HTEvent40 extends HTEvent
{
	/**
	 * @return Integer
	 */
	public function getTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getPossesionPercent()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}
}
class HTEvent41 extends HTEvent
{
	const VALUE1 = 'Best player';

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return Integer
	 */
	public function getPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getPlayersTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}
}
class HTEvent42 extends HTEvent
{
	const VALUE1 = 'Worst player';

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return Integer
	 */
	public function getPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getPlayersTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}
}
class HTEvent45 extends HTEvent
{
	const VALUE1 = 'Half time';

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}
}
class HTEvent46 extends HTEvent
{
	const VALUE1 = 'Hattrick';

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return Integer
	 */
	public function getPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getPlayersTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}
}
class HTEvent47 extends HTEvent
{
}
class HTEvent55 extends HTEvent
{
	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}
}
class HTEvent56 extends HTEvent
{
	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}
}
class HTEvent57 extends HTEvent
{
	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}
}
class HTEvent58 extends HTEvent
{
	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}
}
class HTEvent59 extends HTEvent
{
	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}
}
class HTEvent60 extends HTEvent
{
	/**
	 * @return Integer
	 */
	public function getTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}
}
class HTEvent61 extends HTEvent
{
	/**
	 * @return Boolean
	 */
	public function isUp()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}
}
class HTEvent62 extends HTEvent
{
	/**
	 * @return Integer
	 */
	public function getTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}
}
class HTEvent63 extends HTEvent
{
	/**
	 * @return Boolean
	 */
	public function isUp()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}
}
class HTEvent64 extends HTEvent
{
	/**
	 * @return Boolean
	 */
	public function isUp()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}
}
class HTEvent65 extends HTEvent
{
	/**
	 * @return Boolean
	 */
	public function isUp()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}
}
class HTEvent68 extends HTEvent
{
	/**
	 * @return Integer
	 */
	public function getTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}
}
class HTEvent70 extends HTEvent
{
	/**
	 * @return Boolean
	 */
	public function isStart()
	{
		return true;
	}
}
class HTEvent71 extends HTEvent
{
	/**
	 * @return Boolean
	 */
	public function isStart()
	{
		return false;
	}
}
class HTEvent72 extends HTEvent
{
	/**
	 * @return Boolean
	 */
	public function isStart()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getWinnerTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}
}
class HTEvent73 extends HTEvent
{
	/**
	 * @return Boolean
	 */
	public function isStart()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getWinnerTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}
}
class HTEvent90 extends HTEvent
{
	/**
	 * @return Integer
	 */
	public function getInjuredPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getInjuredPlayersTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}
}
class HTEvent91 extends HTEvent
{
	/**
	 * @return Integer
	 */
	public function getInjuredPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getInjuredPlayersTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getReplacementPlayerId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}
}
class HTEvent92 extends HTEvent
{
	/**
	 * @return Integer
	 */
	public function getInjuredPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getInjuredPlayersTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getReplacementPlayerId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}
}
class HTEvent93 extends HTEvent
{
	/**
	 * @return Integer
	 */
	public function getInjuredPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getInjuredPlayersTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}
}
class HTEvent94 extends HTEvent
{
	/**
	 * @return Integer
	 */
	public function getInjuredPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getInjuredPlayersTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}
}
class HTEvent95 extends HTEvent
{
	/**
	 * @return Integer
	 */
	public function getInjuredPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getInjuredPlayersTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getReplacementPlayerId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}
}
class HTEvent96 extends HTEvent
{
	/**
	 * @return Integer
	 */
	public function getInjuredPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getInjuredPlayersTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}
}
class HTEvent97 extends HTEvent
{
	/**
	 * @return Integer
	 */
	public function getInjuredPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getInjuredPlayersTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}
}
class HTEvent100 extends HTEvent
{
	const VALUE1 = 'Free kick';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}
}
class HTEvent101 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'middle';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent102 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'left';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent103 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'right';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent104 extends HTEvent
{
	const VALUE1 = 'Penalty';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}
}
class HTEvent105 extends HTEvent
{
	const VALUE1 = 'Unpredictable';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getSpecialEventPlayerId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return Boolean
	 */
	public function isPositiveEvent()
	{
		return true;
	}
}
class HTEvent106 extends HTEvent
{
	const VALUE1 = 'Unpredictable';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return Boolean
	 */
	public function isPositiveEvent()
	{
		return true;
	}
}
class HTEvent107 extends HTEvent
{
	const VALUE1 = 'Long shot';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return Boolean
	 */
	public function isPositiveEvent()
	{
		return true;
	}
}
class HTEvent108 extends HTEvent
{
	const VALUE1 = 'Unpredictable';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getSpecialEventPlayerId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return Boolean
	 */
	public function isPositiveEvent()
	{
		return true;
	}
}
class HTEvent109 extends HTEvent
{
	const VALUE1 = 'Unpredictable';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getSpecialEventPlayerId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return Boolean
	 */
	public function isPositiveEvent()
	{
		return false;
	}
}
class HTEvent110 extends HTEvent
{
	const VALUE1 = 'Free kick';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}
}
class HTEvent111 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'middle';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent112 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'left';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent113 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'right';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent114 extends HTEvent
{
	const VALUE1 = 'Penalty';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}
}
class HTEvent115 extends HTEvent
{
	const VALUE1 = 'Quick';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getSpecialEventPlayerId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return Boolean
	 */
	public function isPositiveEvent()
	{
		return true;
	}
}
class HTEvent116 extends HTEvent
{
	const VALUE1 = 'Quick';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return Boolean
	 */
	public function isPositiveEvent()
	{
		return true;
	}
}
class HTEvent117 extends HTEvent
{
	const VALUE1 = 'Low stamina';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return Boolean
	 */
	public function isPositiveEvent()
	{
		return false;
	}
}
class HTEvent118 extends HTEvent
{
	const VALUE1 = 'Corner';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getSpecialEventPlayerId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return Boolean
	 */
	public function isPositiveEvent()
	{
		return true;
	}
}
class HTEvent119 extends HTEvent
{
	const VALUE1 = 'Corner';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getSpecialEventPlayerId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return Boolean
	 */
	public function isPositiveEvent()
	{
		return true;
	}
}
class HTEvent120 extends HTEvent
{
	const VALUE1 = 'Free kick';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}
}
class HTEvent121 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'middle';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent122 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'left';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent123 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'right';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent124 extends HTEvent
{
	const VALUE1 = 'Penalty';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}
}
class HTEvent130 extends HTEvent
{
	const VALUE1 = 'Free kick';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}
}
class HTEvent131 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'middle';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent132 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'left';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent133 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'right';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent134 extends HTEvent
{
	const VALUE1 = 'Penalty';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}
}
class HTEvent135 extends HTEvent
{
	const VALUE1 = 'High experience';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getSpecialEventPlayerId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return Boolean
	 */
	public function isPositiveEvent()
	{
		return true;
	}
}
class HTEvent136 extends HTEvent
{
	const VALUE1 = 'Low experience';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return Boolean
	 */
	public function isPositiveEvent()
	{
		return false;
	}
}
class HTEvent137 extends HTEvent
{
	const VALUE1 = 'Cross pass';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return Boolean
	 */
	public function isPositiveEvent()
	{
		return true;
	}
}
class HTEvent138 extends HTEvent
{
	const VALUE1 = 'Cross pass';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getSpecialEventPlayerId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return Boolean
	 */
	public function isPositiveEvent()
	{
		return true;
	}
}
class HTEvent139 extends HTEvent
{
	const VALUE1 = 'Technical';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getSpecialEventPlayerId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return Boolean
	 */
	public function isPositiveEvent()
	{
		return true;
	}
}
class HTEvent140 extends HTEvent
{
	const VALUE1 = 'Free kick';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}
}
class HTEvent141 extends HTEvent
{
	const VALUE1 = 'Counter attack';
	const VALUE2 = 'middle';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent142 extends HTEvent
{
	const VALUE1 = 'Counter attack';
	const VALUE2 = 'left';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent143 extends HTEvent
{
	const VALUE1 = 'Counter attack';
	const VALUE2 = 'right';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent150 extends HTEvent
{
	const VALUE1 = 'Free kick';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}
}
class HTEvent151 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'middle';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent152 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'left';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent153 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'right';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent154 extends HTEvent
{
	const VALUE1 = 'Penalty';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}
}
class HTEvent160 extends HTEvent
{
	const VALUE1 = 'Free kick';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}
}
class HTEvent161 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'middle';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent162 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'left';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent163 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'right';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent164 extends HTEvent
{
	const VALUE1 = 'Penalty';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}
}
class HTEvent170 extends HTEvent
{
	const VALUE1 = 'Free kick';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}
}
class HTEvent171 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'middle';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent172 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'left';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent173 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'right';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent174 extends HTEvent
{
	const VALUE1 = 'Penalty';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}
}
class HTEvent180 extends HTEvent
{
	const VALUE1 = 'Free kick';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}
}
class HTEvent181 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'middle';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent182 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'left';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent183 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'right';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent184 extends HTEvent
{
	const VALUE1 = 'Penalty';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}
}
class HTEvent185 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'indirect free kick';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent186 extends HTEvent
{
	const VALUE1 = 'Counter attack';
	const VALUE2 = 'indirect free kick';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent187 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'long-range shot';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return true;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent200 extends HTEvent
{
	const VALUE1 = 'Free kick';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}
}
class HTEvent201 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'middle';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent202 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'left';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent203 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'right';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent204 extends HTEvent
{
	const VALUE1 = 'Penalty';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}
}
class HTEvent205 extends HTEvent
{
	const VALUE1 = 'Unpredictable';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getSpecialEventPlayerId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return Boolean
	 */
	public function isPositiveEvent()
	{
		return true;
	}
}
class HTEvent206 extends HTEvent
{
	const VALUE1 = 'Unpredictable';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return Boolean
	 */
	public function isPositiveEvent()
	{
		return true;
	}
}
class HTEvent207 extends HTEvent
{
	const VALUE1 = 'Long shot';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return Boolean
	 */
	public function isPositiveEvent()
	{
		return true;
	}
}
class HTEvent208 extends HTEvent
{
	const VALUE1 = 'Unpredictable';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getSpecialEventPlayerId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return Boolean
	 */
	public function isPositiveEvent()
	{
		return true;
	}
}
class HTEvent209 extends HTEvent
{
	const VALUE1 = 'Unpredictable';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getSpecialEventPlayerId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return Boolean
	 */
	public function isPositiveEvent()
	{
		return false;
	}
}
class HTEvent210 extends HTEvent
{
	const VALUE1 = 'Free kick';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}
}
class HTEvent211 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'middle';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent212 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'left';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent213 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'right';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent214 extends HTEvent
{
	const VALUE1 = 'Penalty';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}
}
class HTEvent215 extends HTEvent
{
	const VALUE1 = 'Quick';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getSpecialEventPlayerId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return Boolean
	 */
	public function isPositiveEvent()
	{
		return true;
	}
}
class HTEvent216 extends HTEvent
{
	const VALUE1 = 'Quick';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return Boolean
	 */
	public function isPositiveEvent()
	{
		return true;
	}
}
class HTEvent217 extends HTEvent
{
	const VALUE1 = 'Low stamina';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return Boolean
	 */
	public function isPositiveEvent()
	{
		return false;
	}
}
class HTEvent218 extends HTEvent
{
	const VALUE1 = 'Corner';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getSpecialEventPlayerId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return Boolean
	 */
	public function isPositiveEvent()
	{
		return true;
	}
}
class HTEvent219 extends HTEvent
{
	const VALUE1 = 'Corner';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getSpecialEventPlayerId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return Boolean
	 */
	public function isPositiveEvent()
	{
		return true;
	}
}
class HTEvent220 extends HTEvent
{
	const VALUE1 = 'Free kick';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}
}
class HTEvent221 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'middle';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent222 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'left';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent223 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'right';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent224 extends HTEvent
{
	const VALUE1 = 'Penalty';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}
}
class HTEvent230 extends HTEvent
{
	const VALUE1 = 'Free kick';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}
}
class HTEvent231 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'middle';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent232 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'left';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent233 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'right';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent234 extends HTEvent
{
	const VALUE1 = 'Penalty';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}
}
class HTEvent235 extends HTEvent
{
	const VALUE1 = 'High experience';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getSpecialEventPlayerId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return Boolean
	 */
	public function isPositiveEvent()
	{
		return true;
	}
}
class HTEvent236 extends HTEvent
{
	const VALUE1 = 'Low experience';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return Boolean
	 */
	public function isPositiveEvent()
	{
		return false;
	}
}
class HTEvent237 extends HTEvent
{
	const VALUE1 = 'Cross pass';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return Boolean
	 */
	public function isPositiveEvent()
	{
		return true;
	}
}
class HTEvent239 extends HTEvent
{
	const VALUE1 = 'Technical';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getSpecialEventPlayerId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return Boolean
	 */
	public function isPositiveEvent()
	{
		return true;
	}
}
class HTEvent240 extends HTEvent
{
	const VALUE1 = 'Free kick';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}
}
class HTEvent241 extends HTEvent
{
	const VALUE1 = 'Counter attack';
	const VALUE2 = 'middle';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent242 extends HTEvent
{
	const VALUE1 = 'Counter attack';
	const VALUE2 = 'left';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent243 extends HTEvent
{
	const VALUE1 = 'Counter attack';
	const VALUE2 = 'right';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent250 extends HTEvent
{
	const VALUE1 = 'Free kick';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}
}
class HTEvent251 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'middle';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent252 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'left';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent253 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'right';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent254 extends HTEvent
{
	const VALUE1 = 'Penalty';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}
}
class HTEvent260 extends HTEvent
{
	const VALUE1 = 'Free kick';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}
}
class HTEvent261 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'middle';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent262 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'left';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent263 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'right';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent264 extends HTEvent
{
	const VALUE1 = 'Penalty';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}
}
class HTEvent270 extends HTEvent
{
	const VALUE1 = 'Free kick';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}
}
class HTEvent271 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'middle';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent272 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'left';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent273 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'right';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent274 extends HTEvent
{
	const VALUE1 = 'Penalty';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}
}
class HTEvent280 extends HTEvent
{
	const VALUE1 = 'Free kick';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}
}
class HTEvent281 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'middle';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent282 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'left';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent283 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'right';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent284 extends HTEvent
{
	const VALUE1 = 'Penalty';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefendingGoalieId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}
}
class HTEvent285 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'indirect free kick';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent286 extends HTEvent
{
	const VALUE1 = 'Counter attack';
	const VALUE2 = 'indirect free kick';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent287 extends HTEvent
{
	const VALUE1 = 'Regular';
	const VALUE2 = 'long-range shot';

	/**
	 * @return Boolean
	 */
	public function isGoal()
	{
		return false;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackingTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSide()
	{
		return self::VALUE2;
	}
}
class HTEvent301 extends HTEvent
{
	const VALUE1 = 'Rain';
	const VALUE2 = 'Technical';

	/**
	 * @return String
	 */
	public function getWeather()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSpecialty()
	{
		return self::VALUE2;
	}

	/**
	 * @return Integer
	 */
	public function getSpecialEventPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getSpecialEventPlayersTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Boolean
	 */
	public function isPositiveEvent()
	{
		return false;
	}
}
class HTEvent302 extends HTEvent
{
	const VALUE1 = 'Rain';
	const VALUE2 = 'Powerful';

	/**
	 * @return String
	 */
	public function getWeather()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSpecialty()
	{
		return self::VALUE2;
	}

	/**
	 * @return Integer
	 */
	public function getSpecialEventPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getSpecialEventPlayersTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Boolean
	 */
	public function isPositiveEvent()
	{
		return true;
	}
}
class HTEvent303 extends HTEvent
{
	const VALUE1 = 'Sunny';
	const VALUE2 = 'Technical';

	/**
	 * @return String
	 */
	public function getWeather()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSpecialty()
	{
		return self::VALUE2;
	}

	/**
	 * @return Integer
	 */
	public function getSpecialEventPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getSpecialEventPlayersTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Boolean
	 */
	public function isPositiveEvent()
	{
		return true;
	}
}
class HTEvent304 extends HTEvent
{
	const VALUE1 = 'Sunny';
	const VALUE2 = 'Powerful';

	/**
	 * @return String
	 */
	public function getWeather()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSpecialty()
	{
		return self::VALUE2;
	}

	/**
	 * @return Integer
	 */
	public function getSpecialEventPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getSpecialEventPlayersTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Boolean
	 */
	public function isPositiveEvent()
	{
		return false;
	}
}
class HTEvent305 extends HTEvent
{
	const VALUE1 = 'Rain';
	const VALUE2 = 'Quick';

	/**
	 * @return String
	 */
	public function getWeather()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSpecialty()
	{
		return self::VALUE2;
	}

	/**
	 * @return Integer
	 */
	public function getSpecialEventPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getSpecialEventPlayersTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Boolean
	 */
	public function isPositiveEvent()
	{
		return false;
	}
}
class HTEvent306 extends HTEvent
{
	const VALUE1 = 'Sunny';
	const VALUE2 = 'Quick';

	/**
	 * @return String
	 */
	public function getWeather()
	{
		return self::VALUE1;
	}

	/**
	 * @return String
	 */
	public function getSpecialty()
	{
		return self::VALUE2;
	}

	/**
	 * @return Integer
	 */
	public function getSpecialEventPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getSpecialEventPlayersTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Boolean
	 */
	public function isPositiveEvent()
	{
		return false;
	}
}
class HTEvent331 extends HTEvent
{
	const VALUE1 = 'Pressing';

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return Integer
	 */
	public function getTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}
}
class HTEvent332 extends HTEvent
{
	const VALUE1 = 'Counter attack';

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return Integer
	 */
	public function getTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}
}
class HTEvent333 extends HTEvent
{
	const VALUE1 = 'Attack in the middle';

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return Integer
	 */
	public function getTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}
}
class HTEvent334 extends HTEvent
{
	const VALUE1 = 'Attack on wings';

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return Integer
	 */
	public function getTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}
}
class HTEvent335 extends HTEvent
{
	const VALUE1 = 'Creative';

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return Integer
	 */
	public function getTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}
}
class HTEvent350 extends HTEvent
{
	const VALUE1 = 'Deficit';

	/**
	 * @return Integer
	 */
	public function getReplacedPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getReplacedPlayersTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getReplacementPlayerId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getReason()
	{
		return self::VALUE1;
	}
}
class HTEvent351 extends HTEvent
{
	const VALUE1 = 'Lead';

	/**
	 * @return Integer
	 */
	public function getReplacedPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getReplacedPlayersTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getReplacementPlayerId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getReason()
	{
		return self::VALUE1;
	}
}
class HTEvent352 extends HTEvent
{
	const VALUE1 = 'Score independently';

	/**
	 * @return Integer
	 */
	public function getReplacedPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getReplacedPlayersTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getReplacementPlayerId()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return String
	 */
	public function getReason()
	{
		return self::VALUE1;
	}
}
class HTEvent360 extends HTEvent
{
	const VALUE1 = 'Deficit';

	/**
	 * @return Integer
	 */
	public function getTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return String
	 */
	public function getReason()
	{
		return self::VALUE1;
	}
}
class HTEvent361 extends HTEvent
{
	const VALUE1 = 'Lead';

	/**
	 * @return Integer
	 */
	public function getTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return String
	 */
	public function getReason()
	{
		return self::VALUE1;
	}
}
class HTEvent362 extends HTEvent
{
	const VALUE1 = 'Score independently';

	/**
	 * @return Integer
	 */
	public function getTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return String
	 */
	public function getReason()
	{
		return self::VALUE1;
	}
}
class HTEvent500 extends HTEvent
{
	const VALUE1 = 'Draw';

	/**
	 * @return String
	 */
	public function getWinningTeam()
	{
		return self::VALUE1;
	}
}
class HTEvent501 extends HTEvent
{
	const VALUE1 = 'Away team';

	/**
	 * @return String
	 */
	public function getWinningTeam()
	{
		return self::VALUE1;
	}
}
class HTEvent502 extends HTEvent
{
	const VALUE1 = 'Home team';

	/**
	 * @return String
	 */
	public function getWinningTeam()
	{
		return self::VALUE1;
	}
}
class HTEvent510 extends HTEvent
{
	const VALUE1 = 'First booking';

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return Integer
	 */
	public function getPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}
}
class HTEvent511 extends HTEvent
{
	const VALUE1 = 'First booking';

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return Integer
	 */
	public function getPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}
}
class HTEvent512 extends HTEvent
{
	const VALUE1 = 'Second booking';

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return Integer
	 */
	public function getPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}
}
class HTEvent513 extends HTEvent
{
	const VALUE1 = 'Second booking';

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return Integer
	 */
	public function getPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}
}
class HTEvent514 extends HTEvent
{
	const VALUE1 = 'Red Card';

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}

	/**
	 * @return Integer
	 */
	public function getPlayerId()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}
}
class HTEvent550 extends HTEvent
{
	/**
	 * @return Integer
	 */
	public function getTeamId()
	{
		if(!isset($this->subjectTeamID) || $this->subjectTeamID === null)
		{
			$this->subjectTeamID = $this->getXml()->getElementsByTagName('SubjectTeamID')->item(0)->nodeValue;
		}
		return $this->subjectTeamID;
	}

	/**
	 * @return Integer
	 */
	public function getDefenseRating()
	{
		if(!isset($this->objectPlayerID) || $this->objectPlayerID === null)
		{
			$this->objectPlayerID = $this->getXml()->getElementsByTagName('ObjectPlayerID')->item(0)->nodeValue;
		}
		return $this->objectPlayerID;
	}

	/**
	 * @return Integer
	 */
	public function getAttackRating()
	{
		if(!isset($this->subjectPlayerID) || $this->subjectPlayerID === null)
		{
			$this->subjectPlayerID = $this->getXml()->getElementsByTagName('SubjectPlayerID')->item(0)->nodeValue;
		}
		return $this->subjectPlayerID;
	}
}
class HTEvent599 extends HTEvent
{
	const VALUE1 = 'Full time';

	/**
	 * @return String
	 */
	public function getType()
	{
		return self::VALUE1;
	}
}
class HTEvent
{
	const N1 = 'StartingFormation';
	const N2 = 'StartingLineUp';
	const N3 = 'MatchPlace';
	const N4 = 'Weather';
	const N5 = 'Possession';
	const N6 = 'PlayerPerformance';
	const N7 = 'Score';
	const N8 = 'PossesionEquals';
	const N9 = 'ShootoutPenaltyEvent';
	const N10 = 'OverConfidence';
	const N11 = 'Organization';
	const N12 = 'GoingDefensive';
	const N13 = 'PressingSuccessful';
	const N14 = 'Extratime';
	const N15 = 'Bruised';
	const N16 = 'Injury';
	const N17 = 'RegularChanceEvent';
	const N18 = 'SpecialChanceEvent';
	const N19 = 'WeatherEvent';
	const N20 = 'Tactic';
	const N21 = 'Substitution';
	const N22 = 'TacticChange';
	const N23 = 'Walkover';
	const N24 = 'Card';
	const N25 = 'IndirectFreeKickRating';

	const E20 = HTEvent::N1;
	const E21 = HTEvent::N2;
	const E25 = HTEvent::N3;
	const E26 = HTEvent::N3;
	const E30 = HTEvent::N4;
	const E31 = HTEvent::N4;
	const E32 = HTEvent::N4;
	const E33 = HTEvent::N4;
	const E40 = HTEvent::N5;
	const E41 = HTEvent::N6;
	const E42 = HTEvent::N6;
	const E45 = HTEvent::N7;
	const E46 = HTEvent::N6;
	const E47 = HTEvent::N8;
	const E55 = HTEvent::N9;
	const E56 = HTEvent::N9;
	const E57 = HTEvent::N9;
	const E58 = HTEvent::N9;
	const E59 = HTEvent::N9;
	const E60 = HTEvent::N10;
	const E61 = HTEvent::N11;
	const E62 = HTEvent::N12;
	const E63 = HTEvent::N11;
	const E64 = HTEvent::N11;
	const E65 = HTEvent::N11;
	const E68 = HTEvent::N13;
	const E70 = HTEvent::N14;
	const E71 = HTEvent::N14;
	const E72 = HTEvent::N14;
	const E73 = HTEvent::N14;
	const E90 = HTEvent::N15;
	const E91 = HTEvent::N16;
	const E92 = HTEvent::N16;
	const E93 = HTEvent::N16;
	const E94 = HTEvent::N15;
	const E95 = HTEvent::N16;
	const E96 = HTEvent::N16;
	const E97 = HTEvent::N16;
	const E100 = HTEvent::N17;
	const E101 = HTEvent::N17;
	const E102 = HTEvent::N17;
	const E103 = HTEvent::N17;
	const E104 = HTEvent::N17;
	const E105 = HTEvent::N18;
	const E106 = HTEvent::N18;
	const E107 = HTEvent::N18;
	const E108 = HTEvent::N18;
	const E109 = HTEvent::N18;
	const E110 = HTEvent::N17;
	const E111 = HTEvent::N17;
	const E112 = HTEvent::N17;
	const E113 = HTEvent::N17;
	const E114 = HTEvent::N17;
	const E115 = HTEvent::N18;
	const E116 = HTEvent::N18;
	const E117 = HTEvent::N18;
	const E118 = HTEvent::N18;
	const E119 = HTEvent::N18;
	const E120 = HTEvent::N17;
	const E121 = HTEvent::N17;
	const E122 = HTEvent::N17;
	const E123 = HTEvent::N17;
	const E124 = HTEvent::N17;
	const E130 = HTEvent::N17;
	const E131 = HTEvent::N17;
	const E132 = HTEvent::N17;
	const E133 = HTEvent::N17;
	const E134 = HTEvent::N17;
	const E135 = HTEvent::N18;
	const E136 = HTEvent::N18;
	const E137 = HTEvent::N18;
	const E138 = HTEvent::N18;
	const E139 = HTEvent::N18;
	const E140 = HTEvent::N17;
	const E141 = HTEvent::N17;
	const E142 = HTEvent::N17;
	const E143 = HTEvent::N17;
	const E150 = HTEvent::N17;
	const E151 = HTEvent::N17;
	const E152 = HTEvent::N17;
	const E153 = HTEvent::N17;
	const E154 = HTEvent::N17;
	const E160 = HTEvent::N17;
	const E161 = HTEvent::N17;
	const E162 = HTEvent::N17;
	const E163 = HTEvent::N17;
	const E164 = HTEvent::N17;
	const E170 = HTEvent::N17;
	const E171 = HTEvent::N17;
	const E172 = HTEvent::N17;
	const E173 = HTEvent::N17;
	const E174 = HTEvent::N17;
	const E180 = HTEvent::N17;
	const E181 = HTEvent::N17;
	const E182 = HTEvent::N17;
	const E183 = HTEvent::N17;
	const E184 = HTEvent::N17;
	const E185 = HTEvent::N17;
	const E186 = HTEvent::N17;
	const E187 = HTEvent::N17;
	const E200 = HTEvent::N17;
	const E201 = HTEvent::N17;
	const E202 = HTEvent::N17;
	const E203 = HTEvent::N17;
	const E204 = HTEvent::N17;
	const E205 = HTEvent::N18;
	const E206 = HTEvent::N18;
	const E207 = HTEvent::N18;
	const E208 = HTEvent::N18;
	const E209 = HTEvent::N18;
	const E210 = HTEvent::N17;
	const E211 = HTEvent::N17;
	const E212 = HTEvent::N17;
	const E213 = HTEvent::N17;
	const E214 = HTEvent::N17;
	const E215 = HTEvent::N18;
	const E216 = HTEvent::N18;
	const E217 = HTEvent::N18;
	const E218 = HTEvent::N18;
	const E219 = HTEvent::N18;
	const E220 = HTEvent::N17;
	const E221 = HTEvent::N17;
	const E222 = HTEvent::N17;
	const E223 = HTEvent::N17;
	const E224 = HTEvent::N17;
	const E230 = HTEvent::N17;
	const E231 = HTEvent::N17;
	const E232 = HTEvent::N17;
	const E233 = HTEvent::N17;
	const E234 = HTEvent::N17;
	const E235 = HTEvent::N18;
	const E236 = HTEvent::N18;
	const E237 = HTEvent::N18;
	const E239 = HTEvent::N18;
	const E240 = HTEvent::N17;
	const E241 = HTEvent::N17;
	const E242 = HTEvent::N17;
	const E243 = HTEvent::N17;
	const E250 = HTEvent::N17;
	const E251 = HTEvent::N17;
	const E252 = HTEvent::N17;
	const E253 = HTEvent::N17;
	const E254 = HTEvent::N17;
	const E260 = HTEvent::N17;
	const E261 = HTEvent::N17;
	const E262 = HTEvent::N17;
	const E263 = HTEvent::N17;
	const E264 = HTEvent::N17;
	const E270 = HTEvent::N17;
	const E271 = HTEvent::N17;
	const E272 = HTEvent::N17;
	const E273 = HTEvent::N17;
	const E274 = HTEvent::N17;
	const E280 = HTEvent::N17;
	const E281 = HTEvent::N17;
	const E282 = HTEvent::N17;
	const E283 = HTEvent::N17;
	const E284 = HTEvent::N17;
	const E285 = HTEvent::N17;
	const E286 = HTEvent::N17;
	const E287 = HTEvent::N17;
	const E301 = HTEvent::N19;
	const E302 = HTEvent::N19;
	const E303 = HTEvent::N19;
	const E304 = HTEvent::N19;
	const E305 = HTEvent::N19;
	const E306 = HTEvent::N19;
	const E331 = HTEvent::N20;
	const E332 = HTEvent::N20;
	const E333 = HTEvent::N20;
	const E334 = HTEvent::N20;
	const E335 = HTEvent::N20;
	const E350 = HTEvent::N21;
	const E351 = HTEvent::N21;
	const E352 = HTEvent::N21;
	const E360 = HTEvent::N22;
	const E361 = HTEvent::N22;
	const E362 = HTEvent::N22;
	const E500 = HTEvent::N23;
	const E501 = HTEvent::N23;
	const E502 = HTEvent::N23;
	const E510 = HTEvent::N24;
	const E511 = HTEvent::N24;
	const E512 = HTEvent::N24;
	const E513 = HTEvent::N24;
	const E514 = HTEvent::N24;
	const E550 = HTEvent::N25;
	const E599 = HTEvent::N7;

	/**
	 * @var DOMDocument
	 */
	private $xml;
	protected $sujectPlayerId = null;
	protected $sujectTeamId = null;
	protected $objectPlayerId = null;

	/**
	 * @param DOMDocument $xml
	 */
	public function __construct($xml)
	{
		$this->xml = $xml;
	}

	/**
	 * Return xml object
	 *
	 * @param Boolean $asObject
	 * @return DOMDocument
	 */
	public function getXml($asObject = true)
	{
		if($asObject === true)
		{
			return $this->xml;
		}
		return $this->xml->saveXML();
	}

	/**
	 * Return type of event as english string
	 *
	 * @return String
	 */
	public function getEventTypeName()
	{
		$number = substr(get_class($this), strlen(get_class()));
		return constant(get_class().'::E'.$number);
	}

	/**
	 * Returns available methods on event
	 *
	 * @return Array
	 */
	public function getMethodsNames()
	{
		$methods = get_class_methods(get_class($this));
		return array_diff($methods, array('__construct', 'getXml', 'getMethodsNames'));
	}
}
/*-events-*/
