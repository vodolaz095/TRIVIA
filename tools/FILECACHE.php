<?php
class FileCacheException extends Exception
{

}
class FILECACHE
{
	protected static $instance;
	private $folder;


	private static function init()
	{
		if ( is_null(self::$instance) )
		{
			self::$instance = new FILECACHE();
		}
		return self::$instance;
	}

	private function __construct()
	{
		if(defined('FILECACHE_DIR'))
			{
				$this->folder=FILECACHE_DIR;
			}
		else
			{
				if(!is_dir('/var/tmp/trivia/'))
					{
						mkdir('/var/tmp/trivia/',0777);
					}

				$this->folder='/var/tmp/trivia/';
			}
	}

	public static function set($key,$value_or_closure,$duration,$devel=false)
	{
		$ans=FILECACHE::get($key,$devel);
		if($ans)
			{
				return $ans;
			}
		else
			{
				if(is_scalar($value_or_closure))
					$a=$value_or_closure;
				else
					$a=$value_or_closure();

				if($devel)
					$ans='<div title="saved to filecache with key *'.$key.'*" and ttl='.$duration.'>'.$a.'</div>';
				else
					$ans=$a;

				return FILECACHE::setValue($key,$a,$duration,$devel) ? $ans : false;

			}
	}


	protected static function setValue($key,$value,$ttl)
	{
		if(preg_match('~^[a-z0-9_]+$~i',$key))
		{
			$fc=FILECACHE::init();
			$time=$ttl+time();
			foreach (glob($fc->folder.$key.'.*.tmp') as $filename)
			{
				//echo __FUNCTION__.'unlinking '.$filename.PHP_EOL;
				unlink($filename);
			}
			//echo __FUNCTION__.'Setting key '.$key.' to '.$value.PHP_EOL;
			return (file_put_contents($fc->folder.$key.'.'.$time.'.tmp',$value)? $value : false);
		}
		else
		{
			throw new FileCacheException($key.' - is a bad key name!');
		}
	}

	public static  function get($key,$devel=false)
	{
		$fc=FILECACHE::init();
//		echo __FUNCTION__.'getting '.$fc->folder.$key.'.*.tmp'.PHP_EOL;
		foreach (glob($fc->folder.$key.'.*.tmp') as $filename)
		{
//			echo __FUNCTION__.'found '.$filename.PHP_EOL;
			$strlen=strlen($fc->folder);
			if(preg_match('~^([a-z0-9_]+)\.(\d+)\.tmp$~i',substr($filename,$strlen),$a))
				{
//					echo 'key='.$a[1].' expire_in '.($a[2]-time()).PHP_EOL;
					$novue=$a[2]-time();
					if($novue>0)
						{
//							echo __FUNCTION__.'READING FILE '.$filename.PHP_EOL;
							$a=file_get_contents($filename);
							if($devel) $a='<div title="retrived from filecache with key *'.$key.'* from directory *'.$filename.'*" and ttl='.$novue.'>'.$a.'</div>';
							return $a;
						}
					else
						{
//							echo __FUNCTION__.'File is expired!'.PHP_EOL;;
							return false;
						}
				}
			else
				{
					return false;
				}
		}


	}

	public static  function del($key)
	{
		$fc=FILECACHE::init();
		foreach (glob($fc->folder.$key.'.*.tmp') as $filename)
		{
			unlink($filename);
		}
	}

	public static  function flush()
	{
		$fc=FILECACHE::init();
//		echo 'flushing!'.PHP_EOL;
//		echo $fc->folder.'*.tmp'.PHP_EOL;
		foreach (glob($fc->folder.'*.tmp') as $filename)
		{
			if(!unlink($filename)) throw new FileCacheException('Unable to remove filecache entry '.$filename.'!');
		}
		return true;
	}


}


/*
date_default_timezone_set('UTC');

echo 'Closure test:'.PHP_EOL;
//FILECACHE::flush();
$text='Lalala - setted on '.date('r');
echo date('r').PHP_EOL;
echo FILECACHE::run('lalala',function(){
	return 'closure '.date('r');
},5,true).PHP_EOL;;


//echo 'setting = '.FILECACHE::set('blablabla',date('r'),5,true).PHP_EOL;
echo 'getting = '.FILECACHE::get('blablabla',true).PHP_EOL;
//FILECACHE::flush();
*/
