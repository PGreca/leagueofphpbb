<?php
/**
*
* League of PhpBB extension for the phpBB Forum Software package.
*
* @copyright (c) 2017 Antonio PGreca (PGreca)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace pgreca\leagueofphpbb\migrations;

class install_leagueofphpbb extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v32x\v321');
	}
	
	public function update_schema()
	{
		return array(
			'add_columns'	=> array(
				$this->table_prefix.'users' => array(
					'user_summonerName'		=> array('VCHAR:255', ''),
					'user_summonerServer'	=> array('VCHAR:5', ''),
				),
			),
		);
	}
	
	public function revert_schema()
	{
		return array(
			'drop_columns'	=> array(
				$this->table_prefix . 'users' => array(
					'user_summonerName',
					'user_summonerServer'
				),
			),
		);
	}
}