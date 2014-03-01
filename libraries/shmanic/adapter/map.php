<?php

abstract class SHAdapterLink
{
	public static function getUserLink($username = null)
	{
		// TODO: currently is badly inefficient
		if ($type = SHUserHelper::getTypeParam($username))
		{
			$domain = SHUserHelper::getDomainParam($username);

			return array('adapter' => true, 'name' => $type, 'domain' => $domain);
		}

		return array('adapter' => false, 'name' => null, 'domain' => null);
	}
}
