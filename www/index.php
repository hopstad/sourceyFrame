<?php

	/*
	* SourceyFrame
	* Inneholder alt man trenger for å sette opp en side fra scratch
	* Database, login, brukere, SSL/None-ssl Sessions/Cookies, Debug ++
	* @ Versjon 1.0
	* @ Morten Hopstad 2016
	*/
	
	// Inkluder CORE package - Kjører startup
	// Variablene på toppen i sourceyLoader.php må endres for å passe til nettsiden
	require_once('../core/sourceyLoader.php');
	sourceyLoader::run();
	
	// Instansierer hovedklassene (Autoloaded fra CLASSES)
	$DB = new DB(); // Database
	if($GLOBALS['requireLogin']){
		$Users = new Users($DB); // Users og Login
	}
	
	// Includes
	include INCLUDES . 'includes.php';
	
	# Diverse
	// Loginform - Godtar POST logg_inn, brukernavn, passord, hasCheckbox(hidden), husk_meg
	// Basic users DB - username, password, name, email, login_attempts, login_start, group, remember_me_token

?>