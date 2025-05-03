<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */



/**
 * Cron Job URL's
 */

//- Series
$routes->get('/getInteSeries', 'Cron\Series::getSeries');

//- Matches
$routes->get('/getInteMatches', 'Cron\Matches::getMatches');

//- Players
$routes->get('/getMatchePlayers', 'Cron\Players::getMatchPlayers');

//- Live Toss 
$routes->get('createMatchLookUpData', 'Cron\Toss::createMatchLookUpData');
$routes->get('checkLiveToss', 'Cron\Toss::checkLiveToss');
$routes->get('getFinalPlayers', 'Cron\Toss::getFinalPlayers');
$routes->get('createFinalDraftPlayers', 'Cron\Toss::createFinalDraftPlayers');
$routes->get('clearLogs', 'Cron\Scores::clearLogs');


//- Live Scores
$routes->get('checkLiveScores', 'Cron\Scores::getLiveScores');
$routes->get('updateGameWinners', 'Cron\Scores::updateGameWinners');


//- News Apis
$routes->get('/getDailyNews', 'Cron\News::getDailyNews');
$routes->get('/getNewsDescription', 'Cron\News::getNewsDescription');


/**
 * Web Site URL's
 */


$routes->get('getUpComingMatches', 'Matches::upWebComingMatches');
$routes->get('getRunningMatches', 'Matches::upWebRunningMatches');
$routes->get('getCompletedMatches', 'Matches::upWebCompletedMatches');
$routes->get('getMatche/(:any)/Scores', 'Matches::upWebMatcheScores');


//- News Apis

$routes->get('/getLatestNews', 'Cron\News::getLatestNews');
$routes->get('/getAllNews', 'Cron\News::getAllNews');
$routes->get('/getNews/(:any)', 'Cron\News::getNewsDetails');


$routes->post('phonePeCallBack', 'User::phonePeCallBack');


/**
 * App URL's
 */

$routes->get('/', 'Home::index');
$routes->post('login', 'User::login');
$routes->post('signUp', 'User::SignUp');
$routes->post('verifyOTP', 'User::ValidateOTP');


//- Auth Urls
$routes->get('1.0/auth/User/(:any)/getNewToken', 'User::getNewAccessToken', ['filter' => 'Refauth']);

$routes->post('1.0/auth/User/(:any)/logOut', 'User::logOut', ['filter' => 'auth']);
$routes->get('1.0/auth/User/(:any)/appBanners', 'User::appBanners', ['filter' => 'auth']);
$routes->get('1.0/auth/User/(:any)/watchEvents', 'ServerEvents::watchEvents');
$routes->post('1.0/auth/User/(:any)/updateDeviceToken', 'User::updateDeviceToken', ['filter' => 'auth']);


//- Matches Page
$routes->get('1.0/auth/User/(:any)/onGoingMatches', 'Matches::onGoingMatches', ['filter' => 'auth']);
$routes->get('1.0/auth/User/(:any)/upComingMatches', 'Matches::upComingMatches', ['filter' => 'auth']);
$routes->get('1.0/auth/User/(:any)/match/(:any)/Squads', 'Matches::matchSquadsExist', ['filter' => 'auth']);


//- Game Room
//- All Contest Prices
$routes->get('1.0/auth/User/(:any)/allContestPrices', 'Contest::allContestPrices', ['filter' => 'auth']);
//- Contest pricing
$routes->post('1.0/auth/User/(:any)/contest/(:any)/matchType/(:any)', 'Contest::getPricing', ['filter' => 'auth']);
//- Check User Entered Contests in a Match
$routes->get('1.0/auth/User/(:any)/match/(:any)/contests', 'Contest::checkMatchContests', ['filter' => 'auth']);
//- Find User Location By Lat and Long or IpGeo Location
$routes->post('1.0/auth/User/(:any)/findAllowedLocation', 'User::allowedLocation', ['filter' => 'auth']);
//- Find User KYC Completed Are Not
$routes->get('1.0/auth/User/(:any)/checkKYC', 'User::checkKYC', ['filter' => 'auth']);
//- Down Grade List
$routes->post('1.0/auth/User/(:any)/match/(:any)/getAllDownGradeContests', 'Contest::getAllDownGradeContests', ['filter' => 'auth']);
//- Down Grade Current Contest
$routes->post('1.0/auth/User/(:any)/match/(:any)/contest/(:any)/downGrade', 'Contest::downGradeContest', ['filter' => 'auth']);
//- Create Match Contest
$routes->post('1.0/auth/User/(:any)/match/(:any)/contest/(:any)', 'Contest::createContest', ['filter' => 'auth']);
//- Team Select
$routes->post('1.0/auth/User/(:any)/game/(:any)/team', 'Contest::selectTeam', ['filter' => 'auth']);
//- User Game Players For Drafts
$routes->post('1.0/auth/User/(:any)/game/(:any)/allPlayers', 'Contest::getAllPlayers', ['filter' => 'auth']);
//- User Game Drafted Players 
$routes->get('1.0/auth/User/(:any)/game/(:any)/draftplayers/(:any)', 'Contest::getDraftPlayers', ['filter' => 'auth']);
//- Add Player To Drafts
$routes->post('1.0/auth/User/(:any)/game/(:any)/draftplayer/(:any)', 'Contest::selectDraftPlayer', ['filter' => 'auth']);
//- Remove Player From Draft
$routes->post('1.0/auth/User/(:any)/game/(:any)/removeplayer/(:any)', 'Contest::removeDraftPlayer', ['filter' => 'auth']);
//- Update Draft Players
$routes->post('1.0/auth/User/(:any)/game/(:any)/updateDraftPlayers', 'Contest::updateDraftPlayers', ['filter' => 'auth']);
//- Save Draft
$routes->post('1.0/auth/User/(:any)/game/(:any)/saveDraftPlayers', 'Contest::saveDraftPlayers', ['filter' => 'auth']);
//- Player Stats
$routes->get('1.0/auth/User/(:any)/match/(:any)/player/(:any)/stats', 'Matches::getPlayerStats', ['filter' => 'auth']);
//- Game Final Players
$routes->get('1.0/auth/User/(:any)/game/(:any)/Players11', 'Contest::gatMatchPlayers11', ['filter' => 'auth']);
//- Update Final Players
$routes->post('1.0/auth/User/(:any)/game/(:any)/finalPlayers', 'Contest::updateFinalPlayers', ['filter' => 'auth']);
//- Game Final Selected 6 Players 
$routes->get('1.0/auth/User/(:any)/game/(:any)/finalSixPlayers', 'Contest::gatfinalSixPlayers', ['filter' => 'auth']);
//- Game Select Cap 
$routes->post('1.0/auth/User/(:any)/game/(:any)/selCaps', 'Contest::updateCaptain', ['filter' => 'auth']);
//- Game Final Player Scores
$routes->post('1.0/auth/User/(:any)/game/(:any)/Scores', 'Contest::getGameScores', ['filter' => 'auth']);
//- Match Scores
$routes->get('1.0/auth/User/(:any)/Matche/(:any)/Scores', 'Matches::appMatcheScores', ['filter' => 'auth']);
//- Game Room Details
$routes->post('1.0/auth/User/(:any)/game/(:any)', 'Contest::getGameDetails', ['filter' => 'auth']);


//- My Games
$routes->get('1.0/auth/User/(:any)/allRunningGames', 'Contest::getAllGames', ['filter' => 'auth']);


//- Game History
$routes->get('1.0/auth/User/(:any)/gamesHistory', 'Contest::gamesHistory', ['filter' => 'auth']);


//- Profile
$routes->post('1.0/auth/User/(:any)/profile/update', 'User::UpdateProfile', ['filter' => 'auth']);
$routes->post('1.0/auth/User/(:any)/profile/sendEmailOtp', 'User::sendEmailOtp', ['filter' => 'auth']);
$routes->post('1.0/auth/User/(:any)/profile/sendMobileOtp', 'User::sendMobileOtp', ['filter' => 'auth']);
$routes->post('1.0/auth/User/(:any)/profile/updateEmail', 'User::updateEmail', ['filter' => 'auth']);
$routes->post('1.0/auth/User/(:any)/profile/updateMobile', 'User::updateMobile', ['filter' => 'auth']);
$routes->get('1.0/auth/User/(:any)/profile', 'User::GetProfile', ['filter' => 'auth']);


//- KYC
$routes->post('1.0/auth/User/(:any)/KYC/sendAadhaarOtp', 'User::sendAadhaarOtp', ['filter' => 'auth']);
$routes->post('1.0/auth/User/(:any)/KYC/verifyAadhaarOtp', 'User::verifyAadhaarOtp', ['filter' => 'auth']);
$routes->post('1.0/auth/User/(:any)/KYC/verifyPan', 'User::verifyPan', ['filter' => 'auth']);
$routes->post('1.0/auth/User/(:any)/KYC/bankAccount', 'User::bankAccount', ['filter' => 'auth']);
$routes->get('1.0/auth/User/(:any)/KYC/bankAccount', 'User::getAllBankAccounts', ['filter' => 'auth']);
$routes->post('1.0/auth/User/(:any)/bankAccount/(:any)', 'User::makeBankAccountsPrimary', ['filter' => 'auth']);

 
//- Wallet
$routes->post('1.0/auth/User/(:any)/initiatePayment', 'UserWallet::initiatePayment', ['filter' => 'auth']);
$routes->get('1.0/auth/User/(:any)/WalletDetails', 'UserWallet::WalletDetails', ['filter' => 'auth']);
$routes->get('1.0/auth/User/(:any)/getDetailsForTDS', 'UserWallet::getDetailsForTDS', ['filter' => 'auth']);
$routes->post('1.0/auth/User/(:any)/Withdraw', 'UserWallet::withdrawAmount', ['filter' => 'auth']);


//- Support
$routes->post('1.0/auth/User/(:any)/Support', 'User::sendSupportRequest', ['filter' => 'auth']);


//- Test Data For Simulation 
$routes->get('genarateFackToss', 'User::genarateFackToss');
$routes->get('genarateFackNotif', 'User::genarateFackNotif');

$routes->get('matchtimeupdate', 'Matches::matchtimeupdate');
$routes->post('updatematchtime', 'Matches::updatematchtime');




/**
 * Admin Api's
 */


$routes->post('admin-login', 'Admin::login');
$routes->post('admin-verifyOTP', 'Admin::ValidateOTP');

$routes->get('1.0/auth/admin/User/(:any)/getNewToken', 'Admin::getNewAccessToken', ['filter' => 'Refauth']);

//- Series
$routes->get('1.0/auth/admin/User/(:any)/getSeries', 'Admin\Matches::getSeries', ['filter' => 'auth']);

//- Matches 
$routes->get('1.0/auth/admin/User/(:any)/getMatches', 'Admin\Matches::getMatches', ['filter' => 'auth']);
$routes->get('1.0/auth/admin/User/(:any)/getRunnMatches', 'Admin\Matches::getRunnMatches', ['filter' => 'auth']);



//- Matches Details By Id
$routes->get('1.0/auth/admin/User/(:any)/getMatches/(:any)', 'Admin\Matches::getMatcheDetails', ['filter' => 'auth']);

//- Declare Toss Manually
$routes->post('1.0/auth/admin/User/(:any)/getMatches/(:any)/ManualToss', 'Admin\Matches::ManualToss', ['filter' => 'auth']);

//- Declare Toss Manually
$routes->post('1.0/auth/admin/User/(:any)/getMatches/(:any)/abandonMatch', 'Admin\Matches::abandonMatch', ['filter' => 'auth']);

//- Move Player To Player11
$routes->post('1.0/auth/admin/User/(:any)/getMatches/(:any)/updatePlayer11', 'Admin\Matches::updatePlayer11', ['filter' => 'auth']);
	
//- Anounce Player11
$routes->post('1.0/auth/admin/User/(:any)/getMatches/(:any)/releasePlayer11', 'Admin\Matches::releasePlayer11', ['filter' => 'auth']);

//- Update Match Time 
$routes->post('1.0/auth/admin/User/(:any)/getMatches/(:any)/changeStartTime', 'Admin\Matches::changeStartTime', ['filter' => 'auth']);

//- Remove Match
$routes->post('1.0/auth/admin/User/(:any)/getMatches/(:any)/suspendMatch', 'Admin\Matches::suspendMatch', ['filter' => 'auth']);

//- Financial Summary
$routes->post('1.0/auth/admin/User/(:any)/getDepositSummary', 'Admin\Wallets::getDepositSummary', ['filter' => 'auth']);

$routes->post('1.0/auth/admin/User/(:any)/getWithdrawalSummary', 'Admin\Wallets::getWithdrawalSummary', ['filter' => 'auth']);

$routes->get('1.0/auth/admin/User/(:any)/getStatsSummary', 'Admin\Matches::getStatsSummary', ['filter' => 'auth']);

$routes->get('1.0/auth/admin/User/(:any)/getLiveSummary', 'Admin\Matches::getLiveSummary', ['filter' => 'auth']);

//- User Lists 
$routes->get('1.0/auth/admin/User/(:any)/getAllUsers', 'Admin\Users::getAllUsers', ['filter' => 'auth']);
$routes->post('1.0/auth/admin/User/(:any)/updateKyc', 'Admin\Users::updateUsersKycName', ['filter' => 'auth']);
$routes->post('1.0/auth/admin/User/(:any)/Users/(:any)/status', 'Admin\Users::updateUsersStats', ['filter' => 'auth']);

//- 
$routes->get('1.0/auth/admin/User/(:any)/getAllMatches', 'Admin\Matches::getAllMatches', ['filter' => 'auth']);
$routes->get('1.0/auth/admin/User/(:any)/matches/(:any)/getAllGameRooms', 'Admin\Matches::getAllGameRooms', ['filter' => 'auth']);

//- Simulator
$routes->group('simulator', static function ($routes) {
	$routes->match(['get','post'], 'matchcreate', 'Simulator\Matches::create');
	$routes->get('success', 'Simulator\Matches::success');
});