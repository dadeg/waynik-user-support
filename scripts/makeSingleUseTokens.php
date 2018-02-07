<?php

for ($i=1; $i<=50; $i++) {
	echo "INSERT INTO `single_use_tokens` (`token`, `user_id`) VALUES (LEFT(UUID(),8), ".$i.");";
}