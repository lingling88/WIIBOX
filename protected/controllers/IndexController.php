<?php
/**
 * Index Controller
 * 
 * @author wengebin
 * @date 2013-12-15
 */
class IndexController extends BaseController
{
	// redis object
	private $_redis;

	// curent every usb setting
	private $_usbSet = array();

	// system message
	private $_sys = '';

	// run mode
	private $_runMode = '';

	// default speed
	private $_defaultSpeed = array(
				'GS_D_V2' => '850',
				'GS_S_V2' => '850',
				'GS_S_V3' => '850',
				'A2_S_V1' => '1280',
			);

	// default check mode for single mode
	private $_checkMode_S = array(
				'OPENWRT_GS_D_V2' => 'tty',
				'RASPBERRY_GS_S_V2' => 'lsusb',
				'OPENWRT_GS_S_V3' => 'tty',
				'RASPBERRY_A2_S_V1' => 'spi',
			);

	// default check mode for dule mode
	private $_checkMode_D = array(
				'OPENWRT_GS_D_V2' => 'lsusb',
			);

	/**
	 * init
	 */
	public function init()
	{
		parent::init();		
	}
	
	/**
	 * Index method
	 */
	public function actionIndex()
	{
		//检查是否登入
		Nbt::app() -> login -> checkIsLogin();
		try
		{
			$this->replaceSeoTitle( CUtil::i18n( 'controllers,index_index_seoTitle' ) );

			// open redis
			$redis = $this->getRedis();

			// get default speed
			$intDefaultSpeed = self::getDefaultSpeed();

			// Tip data
			$aryTipData = array();
			$aryBTCData = array();
			$aryLTCData = array();

			$btcVal = $redis->readByKey( 'btc.setting' );
			$ltcVal = $redis->readByKey( 'ltc.setting' );
			$aryBTCData = empty( $btcVal ) ? array() : json_decode( $btcVal , true );
			if ( empty($aryBTCData['speed']) )
				$aryBTCData['speed'] = $intDefaultSpeed;

			$aryLTCData = empty( $ltcVal ) ? array() : json_decode( $ltcVal , true );
			if ( empty($aryLTCData['speed']) )
				$aryLTCData['speed'] = $intDefaultSpeed;

			// get run model
			$strRunMode = $this->getRunMode();

			// if commit save
			if ( Nbt::app()->request->isPostRequest )
			{
				$strBTCAddress = isset( $_POST['address_btc'] ) ? htmlspecialchars( $_POST['address_btc'] ) : '';
				$strBTCAccount = isset( $_POST['account_btc'] ) ? htmlspecialchars( $_POST['account_btc'] ) : '';
				$strBTCPassword = isset( $_POST['password_btc'] ) ? htmlspecialchars( $_POST['password_btc'] ) : '';

				$strLTCAddress = isset( $_POST['address_ltc'] ) ? htmlspecialchars( $_POST['address_ltc'] ) : '';
				$strLTCAccount = isset( $_POST['account_ltc'] ) ? htmlspecialchars( $_POST['account_ltc'] ) : '';
				$strLTCPassword = isset( $_POST['password_ltc'] ) ? htmlspecialchars( $_POST['password_ltc'] ) : '';

				$intSpeed = isset( $_POST['run_speed'] ) ? intval( $_POST['run_speed'] ) : $intDefaultSpeed;

				$strGetRunModel = isset( $_POST['runmodel'] ) ? htmlspecialchars( $_POST['runmodel'] ) : '';
				if ( !empty( $strGetRunModel ) && in_array( $strGetRunModel , array( 'L' , 'LB' ) ) )
				{
					RunModel::model()->storeRunModel( $strGetRunModel );
					$strRunMode = $strGetRunModel;
				}

				$aryBTCData['ad'] = $strBTCAddress;
				$aryBTCData['ac'] = $strBTCAccount;
				$aryBTCData['pw'] = $strBTCPassword;
				$aryBTCData['speed'] = $intSpeed;
				//$aryBTCData['su'] = isset( $aryBTCData['su'] ) ? $aryBTCData['su'] : 1;

				$aryLTCData['ad'] = $strLTCAddress;
				$aryLTCData['ac'] = $strLTCAccount;
				$aryLTCData['pw'] = $strLTCPassword;
				$aryLTCData['speed'] = $intSpeed;
				//$aryLTCData['su'] = isset( $aryLTCData['su'] ) ? $aryLTCData['su'] : 1;

				$boolCheck = CUtil::isParamsEmpty( $aryLTCData );
				if ( $boolCheck === false )
					throw new CModelException( CUtil::i18n( 'exception,scrypt_setting_haveNullData' ));

				// store data
				$redis->writeByKey( 'btc.setting' , json_encode( $aryBTCData ) );
				$redis->writeByKey( 'ltc.setting' , json_encode( $aryLTCData ) );
				$redis->saveData();
				
				$aryTipData['status'] = 'success';
				$aryTipData['text'] = CUtil::i18n('controllers,index_saveData_success');
			}

		} catch ( Exception $e ) { 
			$aryTipData['status'] = 'error';
			$aryTipData['text'] = $e->getMessage();
		}

		$aryData = array();
		$aryData['tip'] = $aryTipData;
		$aryData['btc'] = $aryBTCData;
		$aryData['ltc'] = $aryLTCData;
		$aryData['runmodel'] = $strRunMode;
		$aryData['speed'] = $aryLTCData['speed'];
		$this->render( 'index' , $aryData );
	}

	/**
	 * super mode
	 */
	public function actionMode()
	{
		// deleted
		echo '200';exit;
	}

	/**
	 * restart program
	 */
	public function actionRestart( $_boolIsNoExist = false )
	{
		ini_set( "max_execution_time" , "300" );

		// get system
		$sys = $this->getSystem();

		$redis = $this->getRedis();
		$restartData = json_decode( $redis->readByKey( 'restart.status' ) , 1 );

		if ( empty( $restartData ) )
			$restartData = array( 'status'=>0 , 'time'=>0 );

		if ( $restartData['status'] === 1 && !empty( $restartData['time'] ) && time() - $restartData['time'] < 60 )
		{
			if ( $_boolIsNoExist === true )
				return false;
			else
			{
				echo '0';
				exit;
			}
		}

		// set restart status
		$restartData = array( 'status'=>1 , 'time'=>time() );
		$redis->writeByKey( 'restart.status' , json_encode( $restartData ) );

		// get run model
		$strRunMode = $this->getRunMode();

		// shutdown all machine
		$this->actionShutdown( true );

		// restart power
		if ( $sys == 'OPENWRT' )
			CPowerSystem::restartPower( 1000000 );

		// single model or dule model
		$strCheckTar = $this->getCheckMode();

		if ( $sys == 'OPENWRT' )
			$aryUsbCache = UsbModel::model()->getUsbChanging( $strRunMode , 6, $strCheckTar );
		else if ( $sys == 'RASPBERRY' )
			$aryUsbCache = UsbModel::model()->getUsbChanging( $strRunMode , 0.1, $strCheckTar );

		$aryUsb = $aryUsbCache['usb'];

		// if btc machine has restart
		if ( count( $aryUsb ) > 0 && in_array( $strRunMode , array( 'B' , 'LB' ) ) )
		{
			$aryBTCData = $this->getTarConfig( 'btc' );

			$aryConfig = $aryBTCData;
			$aryConfig['ac'] = array_shift( $aryConfig['ac'] );
			$aryConfig['mode'] = $strRunMode === 'LB' ? 'LB-B' : ($strRunMode === 'L' ? 'L-B' : 'B');
			$this->restartByUsb( $aryConfig , 'all' , $strRunMode );
			sleep( 1 );
		}

		// if ltc machine has restart
		if ( count( $aryUsb ) > 0 && in_array( $strRunMode , array( 'L' , 'LB' ) ) ) 
		{
			// get ltc config
			$aryLTCData = $this->getTarConfig( 'ltc' );

			// if tty mode
			if ( $strCheckTar == 'tty' )
			{
				$intUids = $aryLTCData['acc'];
				foreach ( $aryUsb as $usb )
				{
					$aryConfig = $aryLTCData;
					if ( $intUids < 1 )
						$intUids = $aryLTCData['acc'];

					$aryConfig['ac'] = $aryLTCData['ac'][$aryLTCData['acc']-$intUids];
					$aryConfig['mode'] = $strRunMode === 'LB' ? 'LB-L' : 'L';
					$aryConfig['su'] = $aryLTCData['su'];

					$this->restartByUsb( $aryConfig , $usb , $strRunMode );
					$intUids --;
				}
			}
			else if ( $strCheckTar == 'spi' )
			{
				$aryConfig = $aryLTCData;
				$aryConfig['ac'] = $aryLTCData['ac'][0];
				$aryConfig['speed'] = $aryLTCData['speed'];

				$this->restartBySpi( $aryConfig );
			}
			else
			{
				$aryConfig = $aryLTCData;
				$aryConfig['ac'] = $aryLTCData['ac'][0];
				$aryConfig['mode'] = $strRunMode === 'LB' ? 'LB-L' : 'L';
				$aryConfig['speed'] = $aryLTCData['speed'];

				$this->restartByUsb( $aryConfig , 'all' , $strRunMode );
			}
		}

		$restartData = array( 'status'=>0 , 'time'=>time() );
		$redis->writeByKey( 'restart.status' , json_encode( $restartData ) );

		if ( $_boolIsNoExist === false )
		{
			echo '200';exit;
		}
		else return true;
	}

	/**
	 * restart program by spi
	 */
	public function restartBySpi( $_aryConfig = array() , $_strSingleShutDown = '' )
	{
		if ( empty( $_aryConfig ) )
			return false;

		$intRunSpeed = $_aryConfig['speed'];
		$command = SUDO_COMMAND.WEB_ROOT."/soft/cgminer_a2 -o {$_aryConfig['ad']} -u {$_aryConfig['ac']} -p {$_aryConfig['pw']} --A1Pll1 {$intRunSpeed} --A1Pll2 {$intRunSpeed} --A1Pll3 {$intRunSpeed} --A1Pll4 {$intRunSpeed} --A1Pll5 {$intRunSpeed} --A1Pll6 {$intRunSpeed} --diff 16 --api-listen --api-network --cs 8 --stmcu 0 --hwreset --no-submit-stale --lowmem --api-allow W:127.0.0.1 >/dev/null 2>&1 &";

		exec( $command );

		// clear history log
		$redis = $this->getRedis();
		$redis->writeByKey( 'speed.history.log' , '{}' );

		return true;
	}

	/**
	 * restart program by usb
	 */
	public function restartByUsb( $_aryConfig = array() , $_strUsb = '' , $_strUsbModel = '' , $_strSingleShutDown = '' )
	{
		if ( empty( $_aryConfig ) || empty( $_strUsb ) || empty( $_strUsbModel ) )
			return false;

		$aryData = $_aryConfig;
		$startModel = $_strUsbModel;

		if ( empty( $aryData ) )
			return false;

		// get run level
		$intRunLevel = $aryData['mode'] === 'LB-B' ? '11' : ($aryData['mode'] === 'L-B' ? '0' : '16');

		// get btc start command
		if ( in_array( $aryData['mode'] , array( 'LB-B' , 'B' ) ) )
		{
			$aryUsbCache = json_decode( $this->getRedis()->readByKey( 'usb.check.result' ) , 1 );
			$intRunSpeed = $aryUsbCache['hasgd'] === 0 ? $aryData['speed'] : 700;
			/*
			if ( $aryData['mode'] == 'L-B' && $aryUsbCache['hasgd'] === 0 )
				$intRunSpeed = 850;
			if ( $aryData['su'] == 0 )
				$intRunSpeed = 600;
			*/

			$command = SUDO_COMMAND.WEB_ROOT."/soft/cgminer --dif --gridseed-options=baud=115200,freq={$intRunSpeed},chips=5,modules=1,usefifo=0,btc={$intRunLevel} --hotplug=0 -o {$aryData['ad']} -u {$aryData['ac']} -p {$aryData['pw']} {$startUsb} >/dev/null 2>&1 &";
		}
		// get dule mode ltc start command
		else if ( in_array( $startModel , array( 'LB' ) ) && in_array( $aryData['mode'] , array( 'LB-L' ) ) )
			$command = SUDO_COMMAND.WEB_ROOT."/soft/minerd{$modelLParam} --dif={$_strUsb} -o {$aryData['ad']} -u {$aryData['ac']} -p {$aryData['pw']} --dual >/dev/null 2>&1 &";
		// get single mode ltc start command
		else if ( in_array( $startModel , array( 'L' ) ) && in_array( $aryData['mode'] , array( 'L' ) ) )
		{
			$intRunSpeed = $aryData['speed'];
			//$command = SUDO_COMMAND.WEB_ROOT."/soft/minerd{$modelLParam} -G {$_strUsb} --freq={$intRunSpeed} --dif={$_strUsb} -o {$aryData['ad']} -u {$aryData['ac']} -p {$aryData['pw']} >/dev/null 2>&1 &";
			$command = SUDO_COMMAND.WEB_ROOT."/soft/cgminer_ltc --dif --gridseed-options=baud=115200,freq={$intRunSpeed},modules=1,chips=40,usefifo=0 --hotplug=0 -o {$aryData['ad']} -u {$aryData['ac']} -p {$aryData['pw']} --api-listen >/dev/null 2>&1 &";
		}

		exec( $command );
		return true;
	}

	/**
	 * shutdown program
	 */
	public function actionShutdown( $_boolIsNoExist = false , $_strSingleShutDown = '' )
	{
		// get cgminer and cpumienr run command
		$command = SUDO_COMMAND.'ps'.( SUDO_COMMAND === '' ? '' : ' -x' ).'|grep miner';
		exec( $command , $output );

		$pids = array();
		$singlePids = array();
		foreach ( $output as $r )
		{
			preg_match( '/\s*(\d+)\s*.*/' , $r , $match );
			if ( !empty( $match[1] ) ) $pids[] = $match[1];

			if ( !empty( $_strSingleShutDown ) )
			{
				preg_match( '/.*--dif=(.+?)\s.*/' , $r , $match_usb );
				if ( in_array( $_strSingleShutDown , array( $match_usb[1] ) ) ) $singlePids[] = $match[1];
			}
		}

		if ( !empty( $_strSingleShutDown ) )
			exec( SUDO_COMMAND.'kill -9 '.implode( ' ' , $singlePids ) );
		else if ( !empty( $pids ) )
			exec( SUDO_COMMAND.'kill -9 '.implode( ' ' , $pids ) );
		
		if ( $_boolIsNoExist === false )
		{
			echo '200';exit;
		}
		else return true;
	}

	/**
	 * check state
	 */
	public function actionCheck( $_boolIsNoExist = false )
	{
		//以下代码仅供测试使用
		$aryData = SpeedModel::model() -> getCheckDataCurl();
		echo json_encode( $aryData );exit;
		
		
		//正式代码
		// get run model
		$strRunMode = $this->getRunMode();

		$command = SUDO_COMMAND.'ps'.( SUDO_COMMAND === '' ? '' : ' -x' ).'|grep miner';
		exec( $command , $output );

		// default null object
		$alived = array('BTC'=>array(),'LTC'=>array());
		$died = array('BTC'=>array(),'LTC'=>array());

		// Alived machine
		$alivedLTCUsb = array();

		// get usb machine and run model
		$strCheckTar = $this->getCheckMode();
		$aryUsb = UsbModel::model()->getUsbCheckResult( $strRunMode , $strCheckTar );
		$allUsbCache = $aryUsb['usb'];

		$alivedBTC = false;
		$alivedLTC = false;

		foreach ( $output as $r )
		{
			preg_match( '/.*(cgminer).*/' , $r , $match_btc );
			preg_match( '/.*(minerd|cgminer_ltc|cgminer_a2).*/' , $r , $match_ltc );

			// if LTC model
			if ( !empty( $match_ltc[1] ) && $alivedLTC === false )
				$alivedLTC = true;
			// if BTC model
			else if ( !empty( $match_btc[1] ) && empty( $match_ltc[1] ) && $alivedBTC === false )
				$alivedBTC = true;
		}

		// Alived machine
		if ( in_array( $strRunMode , array( 'B' , 'LB' ) ) && $alivedBTC === true )
			$alived['BTC'] = $allUsbCache;
		if ( in_array( $strRunMode , array( 'L' , 'LB' ) ) && $alivedLTC === true )
			$alived['LTC'] = $allUsbCache;

		sort( $alived['BTC'] );
		sort( $alived['LTC'] );

		// Died machine
		if ( in_array( $strRunMode  , array( 'B' , 'LB' ) ) && $alivedBTC === false )
			$died['BTC'] = $allUsbCache;
		if ( in_array( $strRunMode  , array( 'L' , 'LB' ) ) && $alivedLTC === false )
			$died['LTC'] = $allUsbCache;

		sort( $died['BTC'] );
		sort( $died['LTC'] );
		
		// return data
		$aryData = array();
		$aryData['alived'] = $alived;
		$aryData['died'] = $died;
		$aryData['super'] = $this->getSuperModelState();

		if ( $_boolIsNoExist === false )
		{
			echo json_encode( $aryData );exit;
		}
		else 
			return $aryData;
	}

	/**
	 * check run state
	 */
	public function actionCheckrun()
	{
		$redis = $this->getRedis();
		$upstatus = json_decode( $redis->readByKey( 'upgrade.run.status' ) , 1 );
		$restartData = json_decode( $redis->readByKey( 'restart.status' ) , 1 );

		$now = time();
		if ( ( $upstatus['status'] === 1 
					&& !empty( $upstatus['time'] ) 
					&& $now - $upstatus['time'] < 60 
					&& $now - $upstatus['time'] >= 0 )
				|| ( $restartData['status'] === 1 
					&& !empty( $restartData['time'] ) 
					&& $now - $restartData['time'] < 30 
					&& $now - $upstatus['time'] >= 0 ) )
		{
			echo '0';
			exit;
		}
		
		// check upgrade file
		if ( $upstatus['status'] === 0 )
			RunModel::model()->checkUpgrade();

		// parse log
		$this->clearLog();

		// reset usb state
		$this->actionUsbstate( true );

		// check data
		$aryData = $this->actionCheck( true );

		// get run model
		$strRunMode = $this->getRunMode();

		if ( empty( $upstatus['time'] ) || $now - $upstatus['time'] >= 60 || $now - $upstatus['time'] < 0 )
		{
			$upstatus = array( 'status'=>0 , 'time'=>$now );
			$redis->writeByKey( 'upgrade.run.status' , json_encode( $upstatus ) );
		}

		if ( empty( $restartData['time'] ) || $now - $restartData['time'] >= 30 || $now - $restartData['time'] < 0 )
		{
			$restartData = array( 'status'=>0 , 'time'=>$now );
			$redis->writeByKey( 'restart.status' , json_encode( $restartData ) );
		}
		
		$intCountMachine = max( 
				count( $aryData['alived']['BTC'] )+count( $aryData['died']['BTC'] ) , 
				count( $aryData['alived']['LTC'] )+count( $aryData['died']['LTC'] ) );

		// if need restart
		if ( ( $strRunMode === 'LB' 
					&&  $intCountMachine > 0 
					&& ( count( $aryData['alived']['BTC'] ) === 0 
						|| count( $aryData['alived']['LTC'] ) === 0 ) )
				|| ( $strRunMode === 'L' 
					&& $intCountMachine > 0 
					&& count( $aryData['alived']['LTC'] ) === 0 ) 
		)
			echo $this->actionRestart( true ) === true ? 1 : -1;
		else
			echo 2;
		exit;
	}

	/**
	 * check usb state
	 */
	public function actionUsbstate( $_boolIsReturn = false )
	{
		$usbData = array('OK'=>0);

		// get run model
		$strRunMode = $this->getRunMode();
		
		if ( $strRunMode === 'L' )
		{
			// find new usb machine
			$strCheckTar = $this->getCheckMode();
			$aryUsbCache = UsbModel::model()->getUsbCheckResult( $strRunMode , $strCheckTar );
			$aryUsb = $aryUsbCache['usb'];

			// get running programe
			$command = SUDO_COMMAND.'ps'.( SUDO_COMMAND === '' ? '' : ' -x' ).'|grep miner';
			exec( $command , $grepout );

			$boolIsLTCAlived = false;
			foreach ( $grepout as $r )
			{
				preg_match( '/.*(minerd|cgminer_ltc|cgminer_a2).*/' , $r , $match_ltc );
				if ( !empty( $match_ltc[1] ) )
				{
					$boolIsLTCAlived = true;
					break;
				}
			}

			if ( count( $aryUsb ) === 0 )
				$this->actionShutdown( true );

			if ( $boolIsLTCAlived === false && count( $aryUids ) > 0 )
				$this->actionRestart( true );

			$usbData['OK'] = 1;
		}

		if ( $_boolIsReturn === false )
		{
			echo json_encode( $usbData );
			exit;
		}
		else
			return $usbData;
	}

	/**
	 * restart target usb
	 */
	public function actionRestartTarget( $_strUsb = '' , $_strModel = '' , $_strTo = '' , $_boolIsNoExist = false )
	{
		$setUsbKey = $_strUsb;

		if ( empty( $setUsbKey ) )
		{
			if ( $_boolIsNoExist === true )
				return false;
			else
				echo '500';exit;
		}

		// get config
		$aryConfig = $this->getTarConfig( $_strModel );
		$aryConfig['ac'] = $aryConfig['ac'][rand(0,$aryConfig['acc']-1)];
		$aryConfig['mode'] = $_strTo;

		$this->restartByUsb( $aryConfig , $setUsbKey , $_strTo , $setUsbKey );

		if ( $_boolIsNoExist === true )
				return true;
			else
				echo '500';exit;
	}

	/**
	 * get super model state
	 */
	public function getSuperModelState()
	{
		/*
		$aryBTCData = $this->getTarConfig( 'btc' );
		return !empty( $aryBTCData ) && intval( $aryBTCData['su'] ) === 1 ? true : false;
		*/
		return true;
	}

	/**
	 * get redis connection
	 */
	public function getRedis()
	{
		if ( empty( $this->_redis ) )
			$this->_redis = new CRedisFile();

		return $this->_redis;
	}

	/**
	 * read default config
	 */
	public function readDefault( $_strTar = '' )
	{
		if ( empty( $_strTar ) )
			return array();

		// Get key
		$os = DIRECTORY_SEPARATOR=='\\' ? "windows" : "linux";
		$mac_addr = new CMac( $os );

		$strRKEY = '';
		if ( file_exists( WEB_ROOT.'/js/RKEY.TXT' ) )
			$strRKEY = file_get_contents( WEB_ROOT.'/js/RKEY.TXT' );

		$strGenerateKey = substr( $_strTar , 0 , 1 ).substr( md5($mac_addr->mac_addr.'-'.$strRKEY) , -10 , 10 );

		$redis = $this->getRedis();
		$strVal = $redis->readByKey( "default.{$_strTar}.setting" );
		$strVal = str_replace( '******' , $strGenerateKey , $strVal );
		return empty( $strVal ) ? array() : json_decode( $strVal , 1 );
	}

	/**
	 * Setting is empty?
	 */
	public function isEmptySetting( $_arySetting = array() )
	{
		if ( empty( $_arySetting['ad'] ) || empty( $_arySetting['ac'] ) )
			return true;
		else
			return false;
	}

	/**
	 * Get config
	 */
	public function getTarConfig( $_strTar = '' )
	{
		if ( empty( $_strTar ) )
			return array();

		// get config
		$redis = $this->getRedis();
		$setVal = $redis->readByKey( "{$_strTar}.setting" );
		$aryData = empty( $setVal ) ? array() : json_decode( $setVal , true );

		if ( $this->isEmptySetting( $aryData ) )
			$aryData = $this->readDefault( $_strTar );

		// parse account
		$strUids = $aryData['ac'];
		$aryUids = explode( ',' , $strUids );
		$aryUidsSet = array();
		foreach ( $aryUids as $id )
		{
			if ( !empty( $id ) )
				$aryUidsSet[] = $id;
		}

		// if speed is null
		if ( empty($aryData['speed']) )
			$aryData['speed'] = self::getDefaultSpeed();

		$aryData['ac'] = $aryUidsSet;
		$aryData['acc'] = count( $aryUidsSet );
		return $aryData;
	}

	/**
	 * clear log
	 */
	public function clearLog()
	{
		// get run mode
		$strRunMode = $this->getRunMode();
		// get check mode
		$strCheckTar = $this->getCheckMode();

		// get usb cache
		$aryUsbCache = UsbModel::model()->getUsbCheckResult( $strRunMode , $strCheckTar );
		$aryUsb = $aryUsbCache['usb'];

		$redis = $this->getRedis();
		$speedLog = $redis->readByKey( 'speed.log' );
		$countLog = $redis->readByKey( 'speed.count.log' );

		$speedData = json_decode( $speedLog , 1 );
		$countData = json_decode( $countLog , 1 );

		$now = time();
		// array( 'BTC'=>array('A'=>100,'R'=>2,'T'=>123456),'LTC'=>array('/dev/ttyUSB0'=>array('A'=>100,'R'=>2,'T'=>123456)) )
		if ( empty( $speedLog ) || empty( $speedData ) )
			$speedData = array('BTC'=>array(),'LTC'=>array());
		if ( empty( $countLog ) || empty( $countData ) )
			$countData = array(
					'BTC'=>array('A'=>0,'R'=>0,'T'=>$now,'LC'=>$now),
					'LTC'=>array('A'=>0,'R'=>0,'T'=>$now,'LC'=>$now)
					);

		// every 30 second clear
		if ( !empty( $speedData['lastlog'] ) 
				&& $now - $speedData['lastlog'] < 30 
				&& $now - $speedData['lastlog'] > -600 )
			return false;

		$boolIsNeedRestart = false;
		$newData = array('BTC'=>array(),'LTC'=>array());

		// if spi mode
		if ( $strCheckTar == 'spi' )
		{
			// get speed data
			$arySpeedData = SpeedModel::getSpeedDataByApi();

			// get history accept
			$historyLog = $redis->readByKey( 'speed.history.log' );
			$aryHistory = json_decode( $historyLog , 1 );

			// high speed
			$doubleHighSpeed = 0;
			foreach ( $arySpeedData as $key=>$data )
				$doubleHighSpeed = max( $doubleHighSpeed , floatval( $data['S'] ) );

			// parse data
			foreach ( $arySpeedData as $key=>$data )
			{
				// more than 5 minutes restart
				if ( $now - $data['LAST'] > 300 )
					$boolIsNeedRestart = true;

				// if speed too low
				if ( $doubleHighSpeed > 0 && $data['RUN'] > 30 && floatval( $data['S'] ) - $doubleHighSpeed * 0.8 < 0 )
					$boolIsNeedRestart = true;

				$intHistoryA = empty( $aryHistory[$key] ) ? 0 : $aryHistory[$key]['A'];
				$countData['LTC']['A'] += intval( $data['A'] ) - $intHistoryA;

				$intHistoryR = empty( $aryHistory[$key] ) ? 0 : $aryHistory[$key]['R'];
				$countData['LTC']['R'] += intval( $data['R'] ) - $intHistoryR;

				$aryHistory[$key]['A'] = intval($data['A']);
				$aryHistory[$key]['R'] = intval($data['R']);
			}

			$countData['LTC']['LC'] = $now;

			// write history log
			$redis->writeByKey( 'speed.history.log' , json_encode( $aryHistory ) );

			// end spi mode
		}
		else
		{
			if ( in_array( $strRunMode , array( 'B' , 'LB' ) ) )
				$newData['BTC'] = $speedData['BTC'];

			if ( in_array( $strRunMode , array( 'L' , 'LB' ) ) )
			{
				foreach ( $speedData['LTC'] as $k=>$d )
				{
					if ( in_array( $k , $aryUsb ) )
						$newData['LTC'][$k] = $d;
				}
			}

			if ( in_array( $strRunMode , array( 'L' , 'LB' ) ) )
			{
				foreach ( $aryUsb as $usb )
				{
					if ( !array_key_exists( $usb , $newData['LTC'] ) )
						$newData['LTC'][$usb] = array( 'A'=>0 , 'R'=>0 , 'T'=>$now);
				}
			}

			$log_dir = '/tmp';
			$btc_log_dir = $log_dir.'/btc';
			$ltc_log_dir = $log_dir.'/ltc';

			if ( file_exists( $btc_log_dir ) )
				$btc_dir_source = opendir( $btc_log_dir );

			$btc_need_check_time = false;
			while ( isset( $btc_dir_source ) && ( $file  = readdir( $btc_dir_source ) ) !== false )
			{
				// get child directory
				$sub_dir = $btc_log_dir.DIRECTORY_SEPARATOR.$file;
				if ( $file == '.' || $file == '..' )
					continue;
				else
				{
					$val = file_get_contents( $sub_dir );
					$valData = explode( '|', $val );
					
					if ( $valData[2] == 'A' )
					{
						$newData['BTC']['A'] ++;
						$countData['BTC']['A'] ++;
					}
					else if ( $valData['2'] == 'R' )
					{
						$newData['BTC']['R'] ++;
						$countData['BTC']['R'] ++;
					}

					$newData['BTC']['T'] = $now;
					$countData['BTC']['T'] = $now;

					unlink( $sub_dir );
					$btc_need_check_time = true;
				}
			}
			
			if ( $btc_need_check_time === true || empty( $countData['BTC']['LC'] ) )
				$countData['BTC']['LC'] = $now;

			// is need restart
			if ( in_array( $strRunMode , array( 'B' , 'LB' ) ) 
					&& ( $btc_need_check_time 
						|| $now - $countData['BTC']['LC'] > 600 
						|| $now - $countData['BTC']['LC'] < 0 
					) 
			)
			{
				if ( $now - $newData['BTC']['T'] > 600 || $now - $newData['BTC']['T'] < 0 )
					$boolIsNeedRestart = true;
			}

			if ( file_exists( $ltc_log_dir ) )
				$ltc_dir_source = opendir( $ltc_log_dir );

			$ltc_need_check_time = false;
			while ( isset( $ltc_dir_source ) && ( $file  = readdir( $ltc_dir_source ) ) !== false )
			{
				// get child directory
				$sub_dir = $ltc_log_dir.DIRECTORY_SEPARATOR.$file;
				if ( $file == '.' || $file == '..' )
					continue;
				else
				{
					$val = file_get_contents( $sub_dir );
					$valData = explode( '|', $val );

					// machine id
					$id = $valData[0];

					if ( !array_key_exists( $id , $newData['LTC'] ) )
					{
						unlink( $sub_dir );
						continue;
					}
				
					if ( $valData[2] == 'A' )
					{
						$newData['LTC'][$id]['A'] ++;
						$countData['LTC']['A'] ++;
					}
					else if ( $valData['2'] == 'R' )
					{
						$newData['LTC'][$id]['R'] ++;
						$countData['LTC']['R'] ++;
					}

					$newData['LTC'][$id]['T'] = $now;
					$countData['LTC']['T'] = $now;

					unlink( $sub_dir );
					$ltc_need_check_time = true;
				}
			}
			
			
			if ( $ltc_need_check_time === true || empty( $countData['LTC']['LC'] ) )
				$countData['LTC']['LC'] = $now;
				
			if ( in_array( $strRunMode , array( 'L' , 'LB' ) ) && ( $ltc_need_check_time || $now - $countData['LTC']['LC'] > 600 || $now - $countData['LTC']['LC'] < 0 ) )
			{
				foreach ( $newData['LTC'] as $m )
				{
					if ( $now - $m['T'] > 600 || $now - $m['T'] < 0 )
					{
						$boolIsNeedRestart = true;
						break;
					}
				}
				
				if ( $boolIsNeedRestart === false && ( $now - $countData['LTC']['LC'] > 600 || $now - $countData['LTC']['LC'] < 0 ) )
					$boolIsNeedRestart = true;
			}

			if ( empty( $speedData['lastlog'] ) )
				$boolIsNeedRestart = false;

			// end tty/lsusb mode
		}

		// check memory
		$strMemoryCmd = 'free -m';
		@exec( $strMemoryCmd , $memory_output );
 	
		preg_match( '/Mem:\s+(\d+)/' , $memory_output[1] , $total_memory );
		$intTotalMemory = $total_memory[1];

		preg_match( '/(\d+)$/' , $memory_output[2] , $free_memory );
		$intFreeMemory = $free_memory[1];

		// if low memory
		$floatFreeMemoryPercent = $intFreeMemory * 100.0 / $intTotalMemory;
		if ( $floatFreeMemoryPercent < 11 )
			$boolIsNeedRestart = true;

		// store clear time stamp
		$newData['lastlog'] = $now;

		// write log
		$redis->writeByKey( 'speed.log' , json_encode( $newData , 1 ) );
		$redis->writeByKey( 'speed.count.log' , json_encode( $countData , 1 ) );

		// if need restart
		if ( $boolIsNeedRestart === true )
		{
			$this->actionRestart( true );
			
			$newData['lastlog'] = $now+300;
			$redis->writeByKey( 'speed.log' , json_encode( $newData , 1 ) );
		}

		return true;
	}

	/**
	 * Get run mode
	 */
	public function getRunMode()
	{
		if ( empty( $this->_runMode ) )
		{
			$strRunMode = RunModel::model()->getRunMode();
			$this->_runMode = $strRunMode;
		}

		return $this->_runMode;
	}

	/**
	 * Get default speed
	 */
	public function getDefaultSpeed()
	{
		return empty( $this->_defaultSpeed[SYS_INFO] ) ? 850 : $this->_defaultSpeed[SYS_INFO];
	}

	/**
	 * Get check mode
	 */
	public function getCheckMode()
	{
		// get current system
		$strSys = $this->getSystem();

		// system info head
		$strSysInfo = $strSys.'_'.SYS_INFO;

		$strRunMode = $this->getRunMode();
		if ( $strRunMode == 'L' )
			return empty( $this->_checkMode_S[$strSysInfo] ) ? 'lsusb' : $this->_checkMode_S[$strSysInfo];
		else
			return empty( $this->_checkMode_D[$strSysInfo] ) ? 'lsusb' : $this->_checkMode_D[$strSysInfo];
	}

	/**
	 * Get system
	 */
	public function getSystem()
	{
		if ( empty( $this->_sys ) )
		{
			// get system
			$sys = new CSys();
			$this->_sys = $sys->cursys;
		}

		return $this->_sys;
	}

//end class
}
