<?php

$path_to_root="../..";

require __DIR__ . '/vendor/autoload.php';

//require_once( 'class.kfLog.php' );	//Extends origin
use ksfraser\kfLog;

define( 'MINMODPATH', 12 );

global $configArray;
$configArray = array();	//Module configs use this

/******************************************************************************************//**
 * This class is the start of a controller, and loads config.*.php files to get sub-modules
 *
 * Routines here have been tested through my own framework, as well as add-ons to 
 * Wordpress, Zencart, Front Accounting.  Doesn't mean this is bug free though!!!
 *
 * Inherits:
        function __construct( $loglevel = PEAR_LOG_DEBUG )
        /*@NULL@* /function set_var( $var, $value )
        function get_var( $var )
        /*@array@* /function var2data()
        /*@array@* /function fields2data( $fieldlist )
        /*@NULL@* /function LogError( $message, $level = PEAR_LOG_ERR )	//Adds to an array only
        /*@NULL@* /function LogMsg( $message, $level = PEAR_LOG_INFO )	//adds to an array only
	function object_var_names()
        function set( $field, $value = null, $enforce_only_native_vars = true )
        /*@NULL@* /function set_var( $var, $value )
	function get( $field )
	function Log( $msg, $level )	//kfLog

 *
 * Provides:
        function __construct( $moduledir )
        function dumpObservers()
        function ObserverRegister( /*Class Instance* /$observer, $event )
         function ObserverDeRegister( $observer )
         function ObserverNotify( $trigger_class, $event, $msg )
         function notified( $object, $event, $message )
 * 
 *
 * ********************************************************************************************/
global $configArray;
$configArray = array(); //Module configs use this

class eventloop extends kfLog implements splSubject
{
	var $config_values = array();   //What fields to be put on config screen
	var $tabs = array();
        var $help_context;
	var $tb_pref;
	private $observers = [];
	private $storage;	//From php.net example
	protected $caller;
	public $moduledir;
	private $logmodnotloaded;
	protected $modulesLoaded;	//<! bool
	private $actions = [];
    private $triggers = [];

	/**//**
	*
	*****/
	function __construct( $moduledir = null, $caller = null )
	{
		parent::__construct();
		$this->caller = $caller;
		$this->storage = new SplObjectStorage();	//php.net
		$this->initEventGroup( '*' );
		$this->initEventGroup( '**' );
		$this->logmodnotloaded = 0;
		$this->modulesLoaded = false;
 		/* 
		 * locate Module class files to open 
		 */
		//if( ! isset( $moduledir ) )
		if( null == $moduledir )
		{
			$moduledir = dirname( __FILE__ ) . '/modules';
			debug_backtrace();
			debug_print_backtrace();
			$this->observerNotify( $this, 'NOTIFY_LOG_INFO', __LINE__ .  "::Moduledir NOT passed into constructor.  Setting default to " . $moduledir );
			
		}
		else
		{
			$this->ObserverNotify( $this, 'NOTIFY_LOG_DEBUG', __LINE__ .  "::Moduledir passed into constructor: " . $moduledir );
			//echo "Moduledir passed into constructor: " . $moduledir;
		}
			
		$this->set_moduledir( $moduledir );
		$this->ObserverNotify( $this, 'NOTIFY_LOG_DEBUG', __LINE__ .  "::Moduledir was set to : " . $this->moduledir );
		//Moving the loading to the controller
		//$this->load_modules();
		//$this->ObserverNotify( $this, 'NOTIFY_LOG_INFO', __LINE__ . "::Completed Adding Modules" );
		//$this->ObserverNotify( $this, 'NOTIFY_INIT_CONTROLLER_COMPLETE', __LINE__ .  "::Completed Adding Modules" );
	}
	/**//*****************************************************************
	* Create the list of events we are interested in handling.
	*
	*
	* As currently desinged, this is an array of the events we can handle
	* and the function to pass the object, data and event code to.
	*
	* @param none
	* @returns none sets ->interestedin
	***********************************************************************/
	function build_interestedin()
	{
		parent::build_interestedin();
		$this->interestedin['NOTIFY_DUMP_OBSERVERS']['function'] = "dumpObservers";
		var_dump( $this->interestedin );
	}
	/**//******************************************************************
	* Set out moduledir 
	*
	* @param string the directory
	* @return none but sets ->moduledir
	***********************************************************************/
	public function set_moduledir(string $dir): void
	{
		global $moduledir;
		//echo get_class( $this ) . "::" . __METHOD__ . "\n\r";
		if( isset( $this->moduledir ) )
		{
			if( strcmp( $dir, $this->moduledir ) !== 0 )
			{
				if( defined( 'REPLACEMODDIROK' ) )
				{
					$this->observerNotify( $this, 'NOTIFY_LOG_INFO', __LINE__ .  "::Moduledir already set:: " . $this->moduledir . " becoming " . $dir . "<br />\r\n");
					$this->moduledir = $dir;
				}
				else
				{
					if( strlen( $this->moduledir < MINMODPATH ) )
					{
						$this->observerNotify( $this, 'NOTIFY_LOG_INFO', __LINE__ .  "::Moduledir :: " . $this->moduledir . " :: becoming ::" . $dir . "<br />\r\n");
						$this->moduledir = $dir;
					}
					else
					{
						$this->observerNotify( $this, 'NOTIFY_LOG_INFO', __LINE__ .  "::Moduledir already set:: " . $this->moduledir . ". Replace NOT OK<br />\r\n" );
					}
				}
			}
		}	
		else
		{
				$this->moduledir = $dir;
				$this->observerNotify( $this, 'NOTIFY_LOG_INFO', __LINE__ .  ":: Setting Moduledir :: " . $this->moduledir . "<br />\r\n");
		}

		if( null == $moduledir )
		{
			$this->observerNotify( $this, 'NOTIFY_LOG_INFO', __LINE__ .  "::Setting GLOBAL Moduledir!! " .  $dir );
			$moduledir = $this->moduledir;
		}
		else
		{
			$this->observerNotify( $this, 'NOTIFY_LOG_DEBUG', __LINE__ .  ":: Global MODULEDIR already set to " . $moduledir . ":: passed in DIR is " . $dir . "\n\r");
		}
	}
	/**/ /**
	*
	*****/
	/*
	function notified( $obj, $event, $msg )
	{
		//echo get_class( $this ) . "::" . __METHOD__ . "\n\r";
		if( $event == "NOTIFY_DUMP_OBSERVERS" )
			$this->dumpObservers();
		else
			parent::notified();
               	return SUCCESS;
	}
 	*/
	/**//**
	* Dump the list of Observers listing the Event and the observer.
	*
	* @param class the class passed in by ObserverNotify.  UNUSED
	* @param string the event passed in by ObserverNotify.  UNUSED
	*****/
	function dumpObservers( $obj, $msg )
	{
		echo get_class( $this ) . "::" . __METHOD__ . "\n\r";
		if( isset( $this->observers ) )
		{
			foreach( $this->observers as $key=>$val )
			{
				echo "Observer Event: " . $key . " with value " . $val . "\n\r";
			}
/*
			foreach( $this->observers as $obs )
			{
				var_dump( $obs );
			}
*/
		}
	}
	 /*****************************************************************************//**
         *ObserverRegister is the fcn that registers a class against an event
         *
         * @param class object to be registered
         * @param string event to register for
         * @param string value not used
         *
         * ******************************************************************************/
	public function ObserverRegister(object $observer, string $event): void
        {
		echo get_class( $this ) . "::" . __METHOD__ . "\n\r";
		//return FALSE;
		$this->initEventGroup( $event );
		echo "Attaching Observer " . get_class( $observer ) . " to event " . $event . "\n\r";
		$this->observers[$event][] = $observer;	//Indirect modification has no effect ERROR
			//->observers is a private var, initialized as an array in class definition.
/*
		try {
			if( isset( $this->observers[$event] ) )
			{
               			$this->observers[$event][] = $observer;	//Indirect modification has no effect ERROR
			}
			else
			{
				$this->observers[$event] = array();
               			$this->observers[$event][] = $observer;	//Indirect modification has no effect ERROR
			}
			//$this->observers[$event] = array_merge( $this->observers[$event], $observer );
               		return SUCCESS;
		}
		catch( Exception $e )
		{
			$this->notify( __METHOD__ . ":" . __LINE__  . " Error: " . $e->getMessage(), "ERROR" );
			$this->notify( __METHOD__ . ":" . __LINE__ . print_r( $this->observers, true ), "WARN" );
        	}
		return FALSE;
*/
	}
	/**//**
	* Remove an Observer
	* 
	* For a small web-app this probably has no utility.
	* For a larger app where this class is re-used this could have some use.
	*
	* @param string class name
	*****/
        public function ObserverDeRegister(object $observer): void
        {
		//echo get_class( $this ) . "::" . __METHOD__ . "\n\r";
              	$this->observers[] = array_diff( $this->observers, array( $observer) );
              	return SUCCESS;
        }
	/**//**********************************************************************
	* Create the initial array entry for an event
	*
	* @param string event
	* @return none sets ->observers
	***************************************************************************/
	private function initEventGroup($event = "*")
 	{
			//echo get_class( $this ) . "::" . __METHOD__ . "\n\r";
		if (!isset($this->observers[$event])) 
		{
	        	$this->observers[$event] = [];
			//echo "Created observer Event Group for " . $event . "\n\r";
	     	}
 	}
        /*****************************************************************************//**
         *ObserverNotify loops throug observers and tells interested ones about the event
         *
	 * @param class
         * @param string event to match against
         * @param mixed ideally is the object that triggered the event
	 * @returns bool
         * *******************************************************************************/
        public function ObserverNotify(object $trigger_class, string $event, $msg): bool
        {
		echo get_class( $this ) . "::" . __METHOD__ . " TRIGGER:: " . get_class( $trigger_class ) . ":: Event::" . $event . ":: msg::" . print_r( $msg ) . "<br />\n\r";
		if( null !== $trigger_class )
		{
			$tclass = get_class( $trigger_class );
		}
		else
		{
			$tclass = "UNDEF";
		}
		if( ! isset( $this->observers[$event] ) )
		{
			switch( $event )
			{
				case 'NOTIFY_LOG_INFO':
			    	case 'NOTIFY_LOG_DEBUG':
					if( $this->logmodnotloaded < 1 )
					{
						echo $tclass . "::Logging Module not loaded!" . "<br />\n\r";
						$this->logmodnotloaded++;
					}
					break;
				default:
					echo "<b>NON REGISTERED EVENT: " . $event . "</b><br />\n\r";
					break;
			}
		}
		else
		{
               		if ( isset( $this->observers[$event] ) )
			{
				if( is_string( $msg ) )
				{
					$this->Log( $tclass  . " had event " . $event . " with message " . $msg, 1 );
				}
				else
				{
					$this->Log( $tclass  . " had event " . $event, 1 );
				}
				echo "Handle EVENT: " . $event . "\n\r"; 
				foreach ( $this->observers[$event] as $obs ) 
				{
					$obs->notified( $trigger_class, $event, $msg );
	        		}
			}
		}
               	/* '**' being used as 'ALL' */
               	if ( isset( $this->observers['**'] ) )
		{
                      	foreach ( $this->observers['**'] as $obs )
                      	{
                              	$obs->notified( $trigger_class, $event, $msg );
                      	}
		}
               	return true;
         }
	/**//***********************************************************
	* Get the list of classes that will react to an event.
	*
	* @param string the event
	* @return array list of classes
	****************************************************************/
	public function getEventObservers(string $event = "*"): array
    	{
		//echo get_class( $this ) . "::" . __METHOD__ . "\n\r";
        	$this->initEventGroup($event);
        	$group = $this->observers[$event];
        	$all = $this->observers["*"];
        	return array_merge($group, $all);
    	}
	/**//**********************************************************
	 * Load modules based upon config files in module directory
	 * 	check Modules for config.*.php	
         *	Add the modules into array of notification observers
         *	using $this->ObserverRegister( / * @class@ * /$observer, / *NOTIFY_XX_XX* / $event, / *@int@* / $priority );
         *	each config file sets variables in $configArray
	 *
         * @startuml
         * partition load_modules {
         * title Load Modules Funcion
         * --> "Sanity check on moduledir"
         * --> "Loop loading the config.*.php files"
         * --> "loop through the loaded configArray setting intermediary carray"
         * --> "loop through carray setting modarray and tabarray.  This allows ordering of handlers"
         * -->(*)
         * }
         * @enduml
	 *
	 * @param none
	 * @return bool did we load modules or not
	 *************************************************************/
	public function load_modules(): bool
	{
		global $MODULESLOADED;
		if( $this->modulesLoaded OR $MODULESLOADED )
		{
			//We've already loaded the modules, don't do it again (until I code a re-load function)
			echo get_class( $this ) . "::" . __METHOD__ . "MODULES are already loaded ";
			return;
		}
		//echo get_class( $this ) . "::" . __METHOD__ . "<br />\n\r";
		global $configArray;
		$confcount = 0;
		//We should probably check for the existance of the moduledir.
		//See Mantis 214 for what triggered this...
		//var_dump( $this->moduledir );
		if( ! is_dir( $this->moduledir ) )
		{
			throw new Exception( "Moduledir does not exist! :: " . $this->moduledir, KSF_INVALID_DATA_VALUE );
		}
	        foreach (glob("{$this->moduledir}/config.*.php") as $filename)
	        {
			$this->log( " opening module config file " . $filename, PEAR_LOG_DEBUG );
			//$this->ObserverNotify( $this, 'NOTIFY_LOG_DEBUG', "<b>Opening Config</b> " . $filename  );
			//$this->ObserverNotify( $this, 'NOTIFY_LOG_DEBUG', "<br />"  );
			//config.X.php has 12 characters so the filename must be at least this
			if( strlen( $filename ) > 12 )
			{
				try
				{
					/*
					* 20230112 BUG FIX:
					* Mantis 2208
					*   having ===FALSE instead of FALSE=== led to include_once not opening anything :(
					*/
	                		if( FALSE === include_once( $filename ) )
					{
						//include failed
						//echo "Reading (including) $filename failed! <br />\n\r";
						//$this->ObserverNotify( $this, 'NOTIFY_LOG_DEBUG', "Opening Config " . $filename  . "Failed!" );
						//$this->ObserverNotify( $this, 'NOTIFY_LOG_DEBUG', "<br />"  );
					}
					else
					{
						$this->ObserverNotify( $this, 'NOTIFY_LOG_DEBUG', "Opened " . $filename  );
						//$this->ObserverNotify( $this, 'NOTIFY_LOG_DEBUG', "<br />"  );
					}
					$confcount++;
				}
				catch( Exception $e )
				{
				}
			}
			else
			{
				$this->ObserverNotify( $this, 'NOTIFY_LOG_DEBUG', "Invalid filename: " . $filename  );
			}
	        }
		
		if( $confcount > 0 )
		{
			$MODULESLADED = $this->modulesLoaded = true;
			echo get_class( $this ) . "::" . __METHOD__ . "Finished loading MODULES ";
		}
		$this->ObserverNotify( $this, 'NOTIFY_LOG_DEBUG', "Tried loading $confcount files" );
		//$this->ObserverNotify( $this, 'NOTIFY_LOG_DEBUG', "<br />"  );
		//var_dump( $configArray );
		//$this->ObserverNotify( $this, 'NOTIFY_LOG_DEBUG', "<br />"  );

		$maxloadpri = 0;
		$maxtabval = 0;
		$configcount = count( $configArray );
		if( isset( $configArray ) AND  $configcount > 0 )
		{
			foreach( $configArray as $carray )
			{
				if( $carray['loadpriority'] > $maxloadpri )
				{
					$maxloadpri = $carray['loadpriority'];
				}
				if( isset( $carray['taborder'] ) )
				{
					if( $taborder > $maxtabval )
					{
						$maxtabval = $taborder;
					}
				}
			}
		}
		else
		{
			//The rest of the function depends on us having loaded configArray data.
			//Otherwise there are no modules to load
			$this->ObserverNotify( $this, 'NOTIFY_LOG_INFO', "No configArray data loaded so no modules to load. " );
			return FALSE;
		}
		//we need to reduce (scale) the load priority 
		$correctionfactor = $maxloadpri / $configcount;
		$tabconvertfactor = $maxtabval / $configcount;

		//We need to fit our data into the smallest arrays possible.
		//maxloadpri is almost certainly larger than configcount unless
		//  we optimized the modules after the fact.
		//Do we provide a hard limit for the number of modules, throwing
		//  away any modules higher than the limit?  Or do we provide a mechanism
		//  to adjust how many the max is?  The MAX is for protecting memory usage...
		if( $configcount > KSF_MAX_MODULES )
		{
			//For now we are making it a hard limit
			$configcount = KSF_MAX_MODULES;
		}
		else
		{
		}

		/*
		 * Loop through the $configArray to set loading modules in right order
		 */
		require_once( 'class.configArrayData.php' );
		$modarray = new SplFixedArray( $configcount + 1 ); //For menu options out of our modules (FA WOO interface driven)
		$tabarray = new SplFixedArray( $configcount + 1 );
		$headptr = $configArrayData = new configArrayData();
			//var_dump( $configArray );
			foreach( $configArray as $carray )
			{
				foreach( $carray as $key => $value )
				{
					$configArrayData->set( $key, $value );
				}
				//var_dump( $configArrayData );
				$pri = (int)round( $carray['loadpriority'] / $correctionfactor, 0, PHP_ROUND_HALF_UP );
				/*
				 * Correction Factor could result in pri being larger than configcount IF we reset configcount to be MAX_MODULES above
				 * In this case we are throwing away any modules whose scaled priority is higher.  This could result in use having less
				 * than MAX_MODULES loaded, since we are looking at priority, and not module counts.
				 */
				if( $pri <= $configcount )
				{
					/*
					 * TODO: We can and almost certainly WILL have index collisions here!
					 */	
					//var_dump( $pri );
					$modarray[$pri] = $configArrayData;
					//$modarray[$carray['loadpriority']][] = $configArrayData;
					//var_dump( $modarray );
				}
				else
				{
					//Pri too high and doesn't fit in the array
					continue;	//Next data item in loop
				}
				//$modarray[$carray['loadpriority']][] = $carray;
				//Add to tabs...
				if( isset( $carray['taborder'] ) )
				{
					$t = (int)round( $carray['taborder'] / $tabconvertfactor, 0 );
                                	$tabarray[ $t ][] = $configArrayData;
                                	//$tabarray[$carray['taborder']][] = $carray;
				}
				else
				{
					$t = rand( 0, $configcount );
                                	$tabarray[ $t ] = $configArrayData;
                                	//$tabarray[99][] = $carray;
				}
				$prevptr = $configArrayData;
				$configArrayData = new configArrayData();
				$configArrayData->set( "prevptr", $prevptr );
				$prevptr->set( "nextptr", $configArrayData );
			}
			unset( $carray );
			//$this->ObserverNotify( $this, 'NOTIFY_LOG_DEBUG', "Resulting TabArray: " );
			//$this->ObserverNotify( $this, 'NOTIFY_LOG_DEBUG', print_r( $tabarray) );
			//$this->ObserverNotify( $this, 'NOTIFY_LOG_DEBUG', "<br />"  );
		unset( $configArray );
		if( isset( $modarray ) AND count( $modarray ) > 0 )
		{
			$this->ObserverNotify( $this, 'NOTIFY_LOG_DEBUG', "Log this line--" . __LINE__  );
			//foreach( $modarray as $priarray )
			foreach( $modarray as $cAD )
			{
				if( null === $cAD )
				{
					continue;
				}
				$res = include_once( $this->moduledir . "/" . $cAD->get( "loadFile" ) );
				if( TRUE == $res )
				{
			$this->ObserverNotify( $this, 'NOTIFY_LOG_DEBUG', "Log this line--" . __LINE__  );
					$tmpClassName = $cAD->get( "objectName" );
					$tmpClass = new $tmpClassName( $this->moduledir );
					if( isset( $tmpClass->observers ) )
					{ 
						foreach( $tmpClass->observers as $obs )
						{
			$this->ObserverNotify( $this, 'NOTIFY_LOG_DEBUG', "Log this line--" . __LINE__  );
							$this->observers[] = $obs;
						}
					}
				}
			}
			$this->ObserverNotify( $this, 'NOTIFY_LOG_DEBUG', "Log this line--" . __LINE__  );
			$this->ObserverNotify( $this, 'NOTIFY_LOG_DEBUG', "Resulting Observers: " );
			$this->ObserverNotify( $this, 'NOTIFY_LOG_DEBUG', print_r( $this->observers) );
			//$this->ObserverNotify( $this, 'NOTIFY_LOG_DEBUG', "<br />"  );
		}
		else
		{
			$this->ObserverNotify( $this, 'NOTIFY_LOG_DEBUG', "Log this line--" . __LINE__  );
			$this->ObserverNotify( $this, 'NOTIFY_LOG_DEBUG', "No modarray to process!! " );
			//$this->ObserverNotify( $this, 'NOTIFY_LOG_DEBUG', "<br />"  );
		}
		$this->ObserverNotify( $this, 'NOTIFY_LOG_INFO', "Log this line--" . __LINE__ . "::Adding Module TABS" );
		$tabs = array();
		if( isset( $tabarray ) AND count( $tabarray ) > 0 )
		{
			//var_dump( $tabarray );
			//foreach( $tabarray as $priarray )
/** Changed to array of classes
			foreach( $tabarray as $priarray )
			{
				var_dump( $priarray );
				foreach( $priarray as $tabinc )
				{
					if( isset( $tabinc['tabdata'] ) )
					{
						$tabs[] = array( 'title' => $tabinc['tabdata']['tabtitle'], 'action' => $tabinc['tabdata']['action'], 'form' => $tabinc['tabdata']['form'], 'hidden' => $tabinc['tabdata']['hidden'], 'class' => $tabinc['tabdata']['class'] );
						$this->ObserverNotify( $this, 'NOTIFY_LOG_INFO', print_r( $tabinc, true ) );
					}
				}
			}
**/
			foreach( $tabarray as $cAD )
			{
				var_dump( $cAD );
				if( null === $cAD )
				{
					continue;
				}
				$tabinc = $cAD->get( "tabs" );
				if( isset( $tabinc['tabdata'] ) )
				{
					$tabs[] = array( 'title' => $tabinc['tabdata']['tabtitle'], 'action' => $tabinc['tabdata']['action'], 'form' => $tabinc['tabdata']['form'], 'hidden' => $tabinc['tabdata']['hidden'], 'class' => $tabinc['tabdata']['class'] );
					$this->ObserverNotify( $this, 'NOTIFY_LOG_INFO', print_r( $tabinc, true ) );
				}
			}
			$this->tabs = $tabs;
			$this->ObserverNotify( $this, 'NOTIFY_TABS_LOADED', $tabs );
		}
		$this->ObserverNotify( $this, 'NOTIFY_LOG_INFO', __LINE__ . "::Completed Adding Modules" );

		return true;
	}
/****************************splSubject************************************************/
	/**//**
	*
	*****/
	public function attach(SplObserver $observer, string $event = "*"): void
    	{
		//echo get_class( $this ) . "::" . __METHOD__ . "\n\r";
        	$this->initEventGroup($event);
        	$this->observers[$event][] = $observer;
		$this->storage->attach($observer);	//php.net
    	}
	/**//**
	*
	*****/
    	public function detach(SplObserver $observer, string $event = "*"): void
    	{
		//echo get_class( $this ) . "::" . __METHOD__ . "\n\r";
		$this->storage->detach($observer);	//php.net
        	foreach ($this->getEventObservers($event) as $key => $s) {
            		if ($s === $observer) {
                		unset($this->observers[$event][$key]);
            		}
        	}
		/********php.net user comments************************/
        	$key = array_search($observer,$this->observers, true);
        	if(false !== $key){
            		unset($this->observers[$key]);
        	}
		/********!php.net user comments************************/
    	}
    	//public function notify(string $event = "*", $data = null)
	/**//**
	*
	*****/
    	public function notify( string $event = "*", $data = null): void
    	{
		//echo get_class( $this ) . "::" . __METHOD__ . "\n\r";
        	foreach ($this->getEventObservers($event) as $observer) {
            		$observer->update($this, $event, $data);
        	}
		//php.net
		foreach ($this->storage as $obj) {
            		$obj->update($this);
        	}
		//!php.net
    	}
/****************************!splSubject************************************************/

    /**
     * Add an action to the event loop.
     *
     * @param string $event
     * @param callable $callback
     * @param int $priority
     */
    public function add_action(string $event, callable $callback, int $priority = 10): void
    {
        $this->actions[$event][$priority][] = $callback;
        ksort($this->actions[$event]);
    }

    /**
     * Execute all actions associated with an event.
     *
     * @param string $event
     * @param mixed $data
     */
    public function do_action(string $event, $data = null): void
    {
        if (isset($this->actions[$event])) {
            foreach ($this->actions[$event] as $priority => $callbacks) {
                foreach ($callbacks as $callback) {
                    call_user_func($callback, $data);
                }
            }
        }
    }

    /**
     * Register a trigger for an event.
     *
     * @param string $event
     * @param callable $trigger
     */
    public function register_trigger(string $event, callable $trigger): void
    {
        $this->triggers[$event][] = $trigger;
    }

    /**
     * Process a workflow by executing all triggers for an event.
     *
     * @param string $event
     * @param mixed $data
     */
    public function process_workflow(string $event, $data = null): void
    {
        if (isset($this->triggers[$event])) {
            foreach ($this->triggers[$event] as $trigger) {
                call_user_func($trigger, $data);
            }
        }
    }
}

