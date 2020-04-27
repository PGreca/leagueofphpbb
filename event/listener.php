<?php
/**
*
* League of PhpBB extension for the phpBB Forum Software package.
*
* @copyright (c) 2020 Antonio PGreca (PGreca)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace pgreca\leagueofphpbb\event;

use phpbb\template\template;
use phpbb\user;
use phpbb\request\request;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use \Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Event listener
 *
 * @package pgreca/leagueofphpbb

 */
class listener implements EventSubscriberInterface
{
	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;
	
	/** @var \phpbb\request\request */
	protected $request;
	
	/**
	 * Constructor.
	 *
	 * @param \phpbb\template\template             $template
	 * @param \phpbb\user                          $user
	 * @param \phpbb\request\request               $request
	 */
	public function __construct(template $template, user $user, request $request)
	{
		$this->template				= $template;
		$this->user					= $user;
		$this->request				= $request;
	}
	
	/**
	 * Decides what listener to use
	 *
	 * @return array
	 */
	static public function getSubscribedEvents()
	{
		return array(
			'core.memberlist_view_profile'							=> 'view_profile',	
			'core.viewtopic_cache_user_data'						=> 'viewtopic_cache_user_data',
			'core.viewtopic_cache_guest_data'						=> 'viewtopic_cache_guest_data',
			'core.viewtopic_modify_post_data'						=> 'viewtopic_modify_post_data',
			'core.viewtopic_modify_post_row'						=> 'viewtopic_modify_post_row',
			'core.ucp_register_data_before'							=> 'user_profile',
			'core.ucp_profile_modify_profile_info'					=> 'user_profile',
			'core.ucp_register_data_after'							=> 'user_profile_validate',
			'core.ucp_profile_validate_profile_info'				=> 'user_profile_validate',
			'core.ucp_profile_info_modify_sql_ary'					=> 'user_profile_sql',
		);
	}
	
	/**
	 * Add info player at profile
	 *
	 * @param \phpbb\event\data $event The event object
	*/
	public function view_profile($event)
	{
		$member = $event['member'];
		$user_id = $member['user_id'];
		
		if ($member['user_summonerServer'] && $member['user_summonerName'])
		{
			$summonerServer = $member['user_summonerServer'];
			$summonerName = $member['user_summonerName'];
			$summonerLvl = $summonerTier = $summonerRank = '';
			$summoner = $this->summmonerInfo($summonerServer, $summonerName);
			
			if ($summoner)
			{
				$summonerLvl = $summoner->lvl;
				$summonerTier = $summoner->tier;
				$summonerRank = $summoner->rank;
			}
			
			$this->template->assign_vars(array(
				'PROFILE_SUMMONERSERVER'		=> $summonerServer,
				'PROFILE_SUMMONERNAME'			=> $summonerName,	
				'PROFILE_SUMMONERLVL'			=> $summonerLvl,	
				'PROFILE_SUMMONERTIER'			=> $summonerTier,
				'PROFILE_SUMMONERRANK'			=> $summonerRank,
			));
		}
	}
	
	/**
	* Update viewtopic user data
	*
	* @param object $event The event object
	* @return null
	* @access public
	*/
	public function viewtopic_cache_user_data($event)
	{

		$array = $event['user_cache_data'];
		$array['user_summonerServer'] = $event['row']['user_summonerServer'];
		$array['user_summonerName'] = $event['row']['user_summonerName'];
		$event['user_cache_data'] = $array;
	}
	
	/**
	* Update viewtopic guest data
	*
	* @param object $event The event object
	* @return null
	* @access public
	*/
	public function viewtopic_cache_guest_data($event)
	{

		$array = $event['user_cache_data'];
		$array['user_summonerServer'] = '';
		$array['user_summonerName'] = '';
		$event['user_cache_data'] = $array;
	}
	
	/**
	 * Loads all user profile player data into the user cache for a topic.
	 *
	 * @param \phpbb\event\data	$event The event data
	 */
	public function viewtopic_modify_post_data($event)
	{
		$array = $event['user_cache_data'];
		$array['user_summonerServer'] = $event['row']['user_summonerServer'];
		$array['user_summonerName'] = $event['row']['user_summonerName'];
		$event['user_cache_data'] = $array;
	}
	
	/**
	 * Assigns user profile player template block variables for a topic post.
	 *
	 * @param \phpbb\event\data	$event The event data
	 */
	public function viewtopic_modify_post_row($event)
	{
		$summoner = $this->summmonerInfo($event['user_poster_data']['user_summonerServer'], $event['user_poster_data']['user_summonerName']);
		
		$event['post_row'] = array_merge($event['post_row'], array(
			'PROFILE_SUMMONERSERVER'		=> $event['user_poster_data']['user_summonerServer'],
			'PROFILE_SUMMONERNAME'			=> $event['user_poster_data']['user_summonerName'],	
			'PROFILE_SUMMONERLVL'			=> $summoner->lvl,	
			'PROFILE_SUMMONERTIER'			=> $summoner->tier,
			'PROFILE_SUMMONERRANK'			=> $summoner->rank
		));
	}
	
	/**
	* Allow users to change their personail info
	*
	* @param object $event The event object
	* @return void
	* @access public
	*/
	public function user_profile($event)
	{
		if (DEFINED('IN_ADMIN'))
		{
			$user_summonerServer = $event['user_row']['user_summonerServer'];
			$user_summonerName = $event['user_row']['user_summonerName'];
		}
		else
		{
			$user_summonerServer = $this->user->data['user_summonerServer'];
			$user_summonerName = $this->user->data['user_summonerName'];
		}

		// Request the user option vars and add them to the data array
		$event['data'] = array_merge($event['data'], array(
			'user_summonerServer'				=> $this->request->variable('user_summonerServer', $user_summonerServer),
			'user_summonerName'					=> $this->request->variable('user_summonerName', $user_summonerName),
		));

		$this->template->assign_vars(array(
			'PROFILE_SUMMONERSERVER'			=> $user_summonerServer,
			'PROFILE_SUMMONERNAME'				=> $user_summonerName,
		));
	}
	
	/**
	* @param object $event The event object
	* @return void
	* @access public
	*/
	public function user_profile_validate($event)
	{
		$array = $event['error'];
		//ensure gender is validated
		if (!function_exists('validate_data'))
		{
			include($this->root_path . 'includes/functions_user.' . $this->php_ext);
		}
		$validate_array = array(
			'user_summonerServer'				=> array('string', true, 1, 5),
			'user_summonerName'					=> array('string', true, 1, 255),
		);
		$error = validate_data($event['data'], $validate_array);
		$event['error'] = array_merge($array, $error);
	}
	
	/**
	* User changed their personal info so update the database
	*
	* @param object $event The event object
	* @return void
	* @access public
	*/
	public function user_profile_sql($event)
	{
		$event['sql_ary'] = array_merge($event['sql_ary'], array(
			'user_summonerServer'				=> $event['data']['user_summonerServer'],
			'user_summonerName'					=> $event['data']['user_summonerName'],
		));
	}
	
	/**
	* @param object $server The server object
	* @param object $player The player object
	* @return array
	* @access public
	*/
	public function summmonerInfo($server, $player)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://pgreca.it/leagueofphpbb.php?server='.$server.'&player='.$player);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$summoner = json_decode(curl_exec($ch));
		curl_close($ch);
	
		return $summoner;
	}
}