<?php

abstract class SHAdapterHelper
{
	/**
	 * Commits the changes to the LDAP user adapter and parses the result.
	 * If any errors occurred then optionally log them and throw an exception.
	 *
	 * @param   SHUserAdaptersLdap  $adapter  LDAP user adapter.
	 * @param   boolean             $log      Log any errors directly to SHLog.
	 * @param   boolean             $throw    Throws an exception on error OR return array on error.
	 *
	 * @return  true|array
	 *
	 * @exception
	 */
	public static function commitChanges($adapter, $log = false, $throw = true)
	{
		$results = $adapter->commitChanges();

		if ($log)
		{
			// Lets log all the commits
			foreach ($results['commits'] as $commit)
			{
				if ($commit['status'] === JLog::INFO)
				{
					SHLog::add($commit['info'], 10634, JLog::INFO, 'ldap');
				}
				else
				{
					SHLog::add($commit['info'], 10636, JLog::ERROR, 'ldap');
					SHLog::add($commit['exception'], 10637, JLog::ERROR, 'ldap');
				}
			}
		}

		// Check if any of the commits failed
		if (!$results['status'])
		{
			if ($throw)
			{
				throw new RuntimeException(JText::_('LIB_SHLDAPHELPER_ERR_10638'), 10638);
			}
			else
			{
				return $results;
			}
		}

		return true;
	}
}
