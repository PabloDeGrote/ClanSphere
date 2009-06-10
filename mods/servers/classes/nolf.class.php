<?php
// No One Lives Forever Game Class
/*
 * Copyright (c) 2004-2006, woah-projekt.de
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 * * Redistributions of source code must retain the above copyright
 *   notice, this list of conditions and the following disclaimer.
 * * Redistributions in binary form must reproduce the above copyright
 *   notice, this list of conditions and the following disclaimer
 *   in the documentation and/or other materials provided with the
 *   distribution.
 * * Neither the name of the phgstats project (woah-projekt.de)
 *   nor the names of its contributors may be used to endorse or
 *   promote products derived from this software without specific
 *   prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

class nolf
{
	var $host     = false;
	var $port     = false;
	var $socket   = false;
	var $g_info   = false;
	var $r_info   = false;
	var $p_info   = false;
	var $response = false;

	function getvalue($srv_value, $srv_data)
	{
		// search the value of selected rule and return it
		$srv_value = array_search ($srv_value, $srv_data);

		if ($srv_value === false)
		{
			return false;
		}
		else
		{
			$srv_value = $srv_data[$srv_value+1];

			return $srv_value;
		}
	}

	function splitdata($stream)
	{
		$cut = strpos($stream, "player_");
		$p_info = substr($stream, $cut);
		$this->p_info = explode("\\", $p_info);
		$this->g_info = explode("\\", $stream);
	}

	function connect()
	{
		if (($this->socket = fsockopen('udp://'. $this->host, $this->port, $errno, $errstr, 30)))
		{
			return true;
		}

		return false;
	}

	function disconnect()
	{
		if ((fclose($this->socket)))
		{
			return true;
		}

		return false;
	}

	function get_info()
	{
		$write = "\\info\\";
		$stream = $this->get_status($write);

		return $stream;
	}
	function get_rules()
	{
		$write = "\\status\\";
		$stream = $this->get_status($write);

		return $stream;
	}

	function get_players()
	{

		$write = "\\players\\";
		$stream = $this->get_status($write);

		return $stream;
	}

	function get_queryid($stream)
	{
		$cache = substr($stream, -1, 1);

		return $cache;
	}

	function rm_queryid($stream)
	{
		$end = strpos($stream, "final\\");
		$cache = substr($stream, 0, $end);

		return $cache;
	}

	function get_status($write)
	{
		$packets = array();

		$ready   = false;
		$timeout = false;

		$packets[0] = false;
		$packets[1] = false;
		$packets[2] = false;
		$packets[3] = false;
		$packets[4] = false;
		$packets[5] = false;

		$info = '';
		$cache = '';
		$finalid = 0;

		if ($this->connect() === false)
		{
			return false;
		}

		socket_set_timeout($this->socket, 1);

		$time_begin = microtime();

		fwrite($this->socket, $write);

		while ($ready == false && $timeout == false)
		{
			$first = fread($this->socket, 1);
			$this->response =  microtime() - $time_begin;

			$status = socket_get_status($this->socket);
			$length = $status['unread_bytes'];

			if ($length > 0)
			{
				$cache = fread($this->socket, $length);
			}

			$id = $this->get_queryid($cache);

			if (stristr($cache, "\\final") && $id == 1)
			{
				$packets[0] = $cache;

				$ready = true;
			}
			elseif (stristr($cache, "\\final\\") && $id > 1)
			{
				$finalid = substr($id, -1, 1);
				$packets[5] = $cache;

				$ready = false;
			}
			elseif ($finalid > 0)
			{
				$packets[$id] = $cache;

				if (count($packets) == $finalid)
				{
					$ready = true;
				}
				else
				{
					$ready = false;
				}
			}
			else
			{
				$packets[$id] = $cache;

				$ready = false;
			}

			if ($status['timed_out'] == true)
			{
				$timeout = true;
			}
		}

		$info  = $this->rm_queryid($packets[0]);
		$info .= $this->rm_queryid($packets[1]);
		$info .= $this->rm_queryid($packets[2]);
		$info .= $this->rm_queryid($packets[3]);
		$info .= $this->rm_queryid($packets[4]);
		$info .= $this->rm_queryid($packets[5]);

		// response time
		$this->response = ($this->response * 1000);
		$this->response = (int)$this->response;

		if ($this->disconnect() === false)
		{
			return false;
		}

		return $info;
	}

	function getstream($host, $port, $queryport)
	{
		if (empty($queryport))
		{
			$this->port = $port;
		}
		else
		{
			$this->port = $queryport;
		}

		$this->host = $host;

		// get the infostream from server
		$this->r_info = $this->get_rules();

		if ($this->r_info)
		{
			$this->splitdata($this->r_info);

			return true;
		}
		else
		{
			return false;
		}
	}

	function check_color($text)
	{
		$clr = array ( // colors
        "\"#000000\"", "\"#DA0120\"", "\"#00B906\"", "\"#E8FF19\"", //  1
        "\"#170BDB\"", "\"#23C2C6\"", "\"#E201DB\"", "\"#FFFFFF\"", //  2
        "\"#CA7C27\"", "\"#757575\"", "\"#EB9F53\"", "\"#106F59\"", //  3
        "\"#5A134F\"", "\"#035AFF\"", "\"#681EA7\"", "\"#5097C1\"", //  4
        "\"#BEDAC4\"", "\"#024D2C\"", "\"#7D081B\"", "\"#90243E\"", //  5
        "\"#743313\"", "\"#A7905E\"", "\"#555C26\"", "\"#AEAC97\"", //  6
        "\"#C0BF7F\"", "\"#000000\"", "\"#DA0120\"", "\"#00B906\"", //  7
        "\"#E8FF19\"", "\"#170BDB\"", "\"#23C2C6\"", "\"#E201DB\"", //  8
        "\"#FFFFFF\"", "\"#CA7C27\"", "\"#757575\"", "\"#CC8034\"", //  9
        "\"#DBDF70\"", "\"#BBBBBB\"", "\"#747228\"", "\"#993400\"", // 10
        "\"#670504\"", "\"#623307\""                                // 11
		);

		// colored numbers
		if ($text <= 39)
		{
			$ctext = "<font color=$clr[7]>$text</font>";
		}
		elseif ($text <= 69)
		{
			$ctext = "<font color=$clr[5]>$text</font>";
		}
		elseif ($text <= 129)
		{
			$ctext = "<font color=$clr[8]>$text</font>";
		}
		elseif ($text <= 399)
		{
			$ctext = "<font color=$clr[9]>$text</font>";
		}
		else
		{
			$ctext = "<font color=$clr[1]>$text</font>";
		}

		return $ctext;
	}

	function getrules($phgdir)
	{
		$srv_rules['sets'] = false;

		// response time
		$srv_rules['response'] = $this->response . ' ms';

		// halo setting pics
    $sets['pass'] = cs_html_img('mods/servers/privileges/pass.gif',0,0,0,'Pass');

		// get the info strings from server info stream
		$srv_rules['hostname']     = $this->getvalue('hostname',   $this->g_info);
		$srv_rules['gametype']     = $this->getvalue('gametype',   $this->g_info);
		$srv_rules['gamename']     = $this->getvalue('gamename',   $this->g_info);
		$srv_rules['mapname']      = $this->getvalue('mapname',    $this->g_info);
		$srv_rules['maxplayers']   = $this->getvalue('maxplayers', $this->g_info);
		$srv_rules['version']      = $this->getvalue('gamever',    $this->g_info);
		$srv_rules['needpass']     = $this->getvalue('password',   $this->g_info);

		// path to map picture and default info picture
		$srv_rules['map_path'] = 'maps/nolf';
		$srv_rules['map_default'] = 'default.jpg';

		// get the connected player
		$srv_rules['nowplayers'] = $this->getvalue('numplayers', $this->g_info);

		// complete the gamename
		$srv_rules['gamename'] = 'No One Lives Forever<br>Version ' . $srv_rules['version'];

		// server privileges
		if ($srv_rules['needpass'] == '1')
		{
			$srv_rules['sets'] .= $sets['pass'];
		}

		if ($srv_rules['sets'] === false)
		{
			$srv_rules['sets'] = '-';
		}

		// return all server rules
		return $srv_rules;
	}

	function getplayers_head() {
		global $cs_lang;
		$head[]['name'] = $cs_lang['rank'];
		$head[]['name'] = $cs_lang['name'];
		$head[]['name'] = $cs_lang['score'];
		$head[]['name'] = $cs_lang['team'];
		$head[]['name'] = $cs_lang['ping'];
		return $head;
	}

	function getplayers()
	{
		$players = array();

		// how many players must search
		$nowplayers = $this->getvalue('numplayers', $this->g_info);

		$clients = 0;

		// get the data of each player and add the team status
		while ($nowplayers != 0)
		{
			$pl       = $this->getvalue("player_$clients", $this->p_info);
			$pl_frags = $this->getvalue("score_$clients",  $this->p_info);
			$pl_team  = $this->getvalue("team_$clients",   $this->p_info);
			$pl_ping  = $this->getvalue("ping_$clients",   $this->p_info);

			/*      if ($pl_team == 0)
			 {
			 $pl_team = 'Allies';
			 }
			 elseif ($pl_team == 1)
			 {
			 $pl_team = 'Axis';
			 }
			 else
			 {
			 $pl_team = 'Spec';
			 }
			 */
			$players[$clients] =
			$pl_frags  . ' ' .
			$pl_team   . ' ' .
			$pl_ping   . ' ' .
            "\"$pl\"";

			$nowplayers--;
			$clients++;
		}

		// check the connected players and sort the ranking
		if ($players == false)
		{
			return array();
		}
		else
		{
			sort($players, SORT_NUMERIC);
		}

		// manage the player data in the following code
		$index = 1;
		$clients = $clients - 1;
		$run=0;
		while ($clients != -1)
		{
			list ($cache[$index], $player[$index]) = split ('\"', $players[$clients]);
			list ($frags[$index],
			$team[$index],
			$ping[$index])  = split (' ',  $cache[$index]);

			$player[$index] = htmlentities($player[$index]);
			$ping[$index]   = $this->check_color($ping[$index]);
				
			$tdata[$run][0] = '<td class="centerb">' . $index . '</td>';
			$tdata[$run][0] .= '<td class="centerb">' . $player[$index] . '</td>';
			$tdata[$run][0] .= '<td class="centerb">' . $frags[$index] . '</td>';
			$tdata[$run][0] .= '<td class="centerb">' . $team[$index] . '</td>';
			$tdata[$run][0] .= '<td class="centerb">' . $ping[$index] . '</td>';

			$run++;
			$index++;
			$clients--;
		}
		return $tdata;
	}
}
