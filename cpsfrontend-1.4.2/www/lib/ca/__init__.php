<?php
/* Copyright (C) 2010-2013 by Contrail Consortium. */


require_module('logging');


/**
 * Generates a new x509 certificate for a manager from scratch
 * (it genertes a key, a request and then the certificate)
 *
 */
function generate_certificate($uid, $sid, $role, $email, $cn, $org) {
	// Get CA cert
	$ca_cert = _get_ca_cert();

	// Generate keypair
	$key = _gen_keypair();
	openssl_pkey_export($key, $key_out);

	// Generate certificate request
	$x509_cert_req = _create_x509_req($key, $uid, $sid,
		$org, $email, $cn, $role);

	// Have the CA sign the req
	$x509_cert = create_x509_cert($x509_cert_req); 	
	openssl_x509_export($x509_cert, $cert, FALSE);

	return 
		array(
			'ca_cert' => $ca_cert,
			'key' => $key_out,
			'cert' => $cert
		);
}

/**
 * Generates a new x509 certificate based on the request
 *
 */
function create_x509_cert($x509_req) {
	$dir = Conf::CONF_DIR.'/certs';
	$ca_cert_file = $dir.'/ca_cert.pem';
	$ca_key_file = $dir.'/ca_key.pem';
	$ca_serial_file = $dir.'/ca_serial';
	
	$cacert = file_get_contents($ca_cert_file);
	$cakey = file_get_contents($ca_key_file);

	// Get serial from file
	$serial = file_get_contents($ca_serial_file);

	// Default serial
	if (!$serial) {
		$serial = 100;
	}

	$serial = (int)$serial;
	
	$configs = array('x509_extensions' => 'v3_req');
	
	// Generate a certificate signing request
	$cert = openssl_csr_sign($x509_req, $cacert,
				$cakey, 365, $configs, intval($serial));
	openssl_x509_export($cert, $csrout, FALSE);
	dlog("Generated new certificate with serial = ".$serial);

	// Increment serial and save it to file
	$serial++;
	file_put_contents($ca_serial_file, $serial);
	
	return $csrout;
}

/**
 * Generates a new x509 certificate request 
 *
 */
function _create_x509_req($key, $userid,
				$serviceid, $org,
				$email, $cn, $role) {
	$dn = array(
		"organizationName" => $org,
		"emailAddress" => $email,
		"commonName" => $cn,
		"userId" => $userid,
		"role" => $role,
		"serviceLocator" => $serviceid
	);

	// Generate a certificate signing request
	$csr = openssl_csr_new($dn, $key);
	//openssl_csr_export($csr, $csrout, FALSE);
	//echo $csrout;
	return $csr;
}

/**
 * Generates a new key pair
 *
 */
function _gen_keypair() {
	return openssl_pkey_new();
}

/**
 * Returns the CA certificate 
 *
 */
function _get_ca_cert() {
	$dir = Conf::CONF_DIR.'/certs';
	$ca_cert_file = $dir.'/ca_cert.pem';
	return file_get_contents($ca_cert_file); 
}

?>
