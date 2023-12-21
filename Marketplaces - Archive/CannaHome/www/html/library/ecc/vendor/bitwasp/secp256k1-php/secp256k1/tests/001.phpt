--TEST--
Check for secp256k1 presence
--SKIPIF--
<?php if (!extension_loaded("secp256k1")) print "skip"; ?>
--FILE--
<?php 
echo "secp256k1 extension is available";
/*
	you can add regression tests for your extension here

  the output of your test code has to be equal to the
  text in the --EXPECT-- section below for the tests
  to pass, differences between the output and the
  expected text are interpreted as failure

	see php7/README.TESTING for further information on
  writing regression tests
*/
?>
--EXPECT--
secp256k1 extension is available
