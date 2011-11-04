<?php
// Copyright (C) 2011 Combodo SARL
//
/**
 * ModelFactory: in-memory manipulation of the XML MetaModel
 *
 * @author      Erwan Taloc <erwan.taloc@combodo.com>
 * @author      Romain Quetiez <romain.quetiez@combodo.com>
 * @author      Denis Flaven <denis.flaven@combodo.com>
 * @license     Combodo Private
 */
 
 /**
 * ModelFactoryModule: the representation of a Module (i.e. element that can be selected during the setup)
 * in the ModelFactory/in-memory XML DataModel
 * @package ModelFactory
 */
class MFModule
{
}

/**
 * ModelFactory: the class that manages the in-memory representation of the XML MetaModel
 * @package ModelFactory
 */
class ModelFactory
{
	protected $sRootDir;
	
	public function __construct($sRootDir)
	{
		$this->sRootDir = $sRootDir;
	}
	
	/**
	 * Loads the definitions corresponding to the given Module
	 * @param MFModule $oModule
	 */
	public function LoadModule(MFModule $oModule)
	{
		
	}
	
	/**
	 * Save the changes to the disk
	 * @param bool $bDevMode
	 */	
	public function SaveChanges($bDevMode = false)
	{
		
	}
	
	/**
	 * Searches on disk in the root directory for module description files
	 * and returns an array of MFModules
	 * @return array Array of MFModules
	 */
	public function FindModules()
	{
		
	}
}