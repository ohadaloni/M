<table border="0">
	<tr class="mHeaderRow">
		<td>{$stats.time}</td>
		<td></td>
		<td>
			<a target="php.net"
				href="http://www.php.net/manual/en/memcache.getstats.php#69727"
				>php.net</a>
			<a target="memcacheReference"
				href="http://code.google.com/p/memcached/wiki/NewServerMaint#Important_Stats"
				>code.google.com</a>
		</td>
	</tr>
	<tr class="mSeparatorRow">
		<td colspan="3" height="10">{***********************}</td>
	</tr>
	<tr class="mRow">
		<td>serverStatus</td>
		<td>{$stats.serverStatus}</td>
		<td></td>
	</tr>
	<tr class="mRow">
		<td>uptime</td>
		<td>{$stats.uptimeStr}</td>
		<td>Number of seconds this server has been running ({$stats.uptime})</td>
	</tr>
	<tr class="mRow">
		<td>accepting_conns</td>
		<td>{$stats.accepting_conns}</td>
		<td>.</td>
	</tr>
	<tr class="mRow">
		<td>rusage_user</td>
		<td>{$stats.rusage_user}</td>
		<td>Accumulated user time for this process</td>
	</tr>
	<tr class="mRow">
		<td>rusage_system</td>
		<td>{$stats.rusage_system}</td>
		<td>Accumulated system time for this process</td>
	</tr>
	<tr class="mSeparatorRow">
		<td colspan="3" height="10">{***********************}</td>
	</tr>
	<tr class="mRow">
		<td>CleanersQ length</td>
		<td><span class="red">{$stats.CleanersQlength}</span></td>
		<td>Cleaners Queue Back Log</td>
	</tr>
	<tr class="mRow">
		<td>curr_items</td>
		<td><span class="red">{$stats.curr_items}</span></td>
		<td>Current number of items stored by the server</td>
	</tr>
	<tr class="mRow">
		<td>total_items</td>
		<td>{$stats.total_items}</td>
		<td>Total number of items stored by this server ever since it started</td>
	</tr>
	<tr class="mRow">
		<td>evictions</td>
		<td>{$stats.evictions}</td>
		<td>Number of valid items removed from cache to free memory for new items</td>
	</tr>
	<tr class="mRow">
		<td>limit_maxbytes</td>
		<td><span class="red">{$stats.limit_maxbytes}</span></td>
		<td>Number of bytes this server is allowed to use for storage</td>
	</tr>
	<tr class="mRow">
		<td>bytes</td>
		<td><span class="red">{$stats.bytes}</span></td>
		<td>Current number of bytes used by this server to store items</td>
	</tr>
	<tr class="mRow">
		<td>bytes_read</td>
		<td>{$stats.bytes_read}</td>
		<td>Total number of bytes read by this server from network</td>
	</tr>
	<tr class="mRow">
		<td>bytes_written</td>
		<td>{$stats.bytes_written}</td>
		<td>Total number of bytes sent by this server to network</td>
	</tr>
	<tr class="mSeparatorRow">
		<td colspan="3" height="10">{***********************}</td>
	</tr>
	<tr class="mRow">
		<td>cmd_get</td>
		<td>{$stats.cmd_get}</td>
		<td>Cumulative number of retrieval requests</td>
	</tr>
	<tr class="mRow">
		<td>cmd_set</td>
		<td>{$stats.cmd_set}</td>
		<td>Cumulative number of storage requests</td>
	</tr>
	<tr class="mSeparatorRow">
		<td colspan="3" height="10">{***********************}</td>
	</tr>
	<tr class="mRow">
		<td>get_hits</td>
		<td>{$stats.get_hits}</td>
		<td>Number of keys that have been requested and found present</td>
	</tr>
	<tr class="mRow">
		<td>get_misses</td>
		<td>{$stats.get_misses}</td>
		<td>Number of items that have been requested and not found</td>
	</tr>
	<tr class="mRow">
		<td>incr_misses</td>
		<td>{$stats.incr_misses}</td>
		<td>.</td>
	</tr>
	<tr class="mRow">
		<td>incr_hits</td>
		<td>{$stats.incr_hits}</td>
		<td>.</td>
	</tr>
	<tr class="mRow">
		<td>decr_misses</td>
		<td>{$stats.decr_misses}</td>
		<td>.</td>
	</tr>
	<tr class="mRow">
		<td>decr_hits</td>
		<td>{$stats.decr_hits}</td>
		<td>.</td>
	</tr>
	<tr class="mSeparatorRow">
		<td colspan="3" height="10">{***********************}</td>
	</tr>
	<tr class="mRow">
		<td>curr_connections</td>
		<td>{$stats.curr_connections}</td>
		<td>Number of open connections</td>
	</tr>
	<tr class="mRow">
		<td>total_connections</td>
		<td>{$stats.total_connections}</td>
		<td>Total number of connections opened since the server started running</td>
	</tr>
	<tr class="mRow">
		<td>connection_structures</td>
		<td>{$stats.connection_structures}</td>
		<td>Number of connection structures allocated by the server</td>
	</tr>
	<tr class="mSeparatorRow">
		<td colspan="3" height="10">{***********************}</td>
	</tr>
	<tr class="mRow">
		<td>auth_cmds</td>
		<td>{$stats.auth_cmds}</td>
		<td>Indicates the total number of authentication attempts</td>
	</tr>
	<tr class="mRow">
		<td>auth_errors</td>
		<td>{$stats.auth_errors}</td>
		<td>Indicates the number of failed authentication attempts</td>
	</tr>
	<tr class="mRow">
		<td>listen_disabled_num</td>
		<td>{$stats.listen_disabled_num}</td>
		<td><a title="An obscure named statistic counting the number of times memcached has hit its connection limit. When memcached hits the max connections setting, it disables its listener and new connections will wait in a queue. When someone disconnects, memcached wakes up the listener and starts accepting again.
		
		Each time this counter goes up, you're entering a situation where new connections will lag. Make sure it stays at or close to zero.">*HOVER*</td>
	</tr>
	<tr class="mRow">
		<td>threads</td>
		<td>{$stats.threads}</td>
		<td>Number of threads the server is running (if built with threading)</td>
	</tr>
	<tr class="mRow">
		<td>conn_yields</td>
		<td>{$stats.conn_yields}</td>
		<td>?</td>
	</tr>
	<tr class="mRow">
		<td>cmd_flush</td>
		<td>{$stats.cmd_flush}</td>
		<td>.</td>
	</tr>
</table>
