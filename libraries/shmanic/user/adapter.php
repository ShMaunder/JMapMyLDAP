<?php


interface SHUserAdapter
{

	public function __construct(array $credentials, $config = null, array $options = array());

	public function getId($authenticate);

	/**
	 * Return specified user attributes from the source.
	 *
	 * @param   string|array  $input  Optional string or array of attributes to return.
	 * @param   boolean       $null   Include null or non existent values.
	 *
	 * @return  mixed  Ldap attribute results.
	 *
	 * @since   2.0
	 */
	public function getAttributes($input = null, $null = false);

	public function setAttributes(array $attributes);

}
