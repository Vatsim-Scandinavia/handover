<?php

return array(
	"database" => array(
		"host" => "localhost",
		"db" => "core",
		"username" => "root",
		"password" => ""
	),
	"environment" => "dev", // Change this between "prod" and "dev" to switch SSO certs and similar.
	"auth" => array(
		// VATSIM SSOv1 Authentication for production
		"prod" => array(
			"base" => "https://cert.vatsim.net/sso/",
			"key" => "accsca",
			"secret" => "7Px_~k6.dFDHJD2g3G7U",
			"method" => "RSA",
			"cert" => <<<EOD
			-----BEGIN RSA PRIVATE KEY-----
			MIIEogIBAAKCAQEAwYR3lUkI4dvuBL1Uh/lG6wdeRrZ6CRnbG7T/So7J9uJv9lNy
			ExHjMlJP4wJ79ycrjijzciC8IZqMI9Zw/AWYdYeksyLQ7pmA7vMmXqllyWHgSz69
			3bKIz3VPI5t0yP3xlS0VVCqEN++aZr6lN4LSmeDs+U3u03CiHUGWrEoFdoTX1XUn
			gI1Dk2BhWC+lkAHuVDjxLI4Qm5kitZ99zxXQGRPTbP+3Lsd5pZA1z/Q2C3nzLeFp
			pwSlPgwb/P1wR4ClujTrsKv+hNbH+hKeZQDNXBO/Hj+zaKooZUVFQXAwYRfkCBH3
			yV0ncLW/YnLxdbT009wPZ4LMBDQNSNXhVXGiXwIDAQABAoIBAHmFuR6rPYOv+5lI
			V8QHmyxOdTqMrrf2X6zXjZWBc3yxF8QlBLbK/dSE8hvJXJWJKIXeW3po6htkFOsQ
			Z+UrkmED7D5995xOEzR6xradRAkhiJtX7B1DVzyIG0lt/vmU1tdp90HibSM/OMOa
			pt/kLGJraATqlrZ6vaMHpBnPZfzVN/Y6+nJ2xN0/Vley8VHtWYtl/Bl6oIAbndhJ
			VkVwFnz5HwsF93TxgXSpRDLii7uTRJaTHkmDmYDJIYRktdwrmkpkaRIeLtgBgYKl
			B4GzwrTRD4CLZXUkxka8eATy65ERL+cPvGphV1z6FsMe1s4ct+0iXFvhUxfkfFjn
			iOg2M+ECgYEA+N+VOVHr9WOo0PQ6MdWbRE4ermWWme54UwVn6XLVVH3WqHdVPIYn
			2SGDZz8e2urj7/0/yO4XNbk2SsuM6x0nncuSGTLzG/V7g4gu04YwEGF0hs1juDdD
			mIXUhUaY3gamxKjFNzdvnii5Vy8c985rdsbWuoXojt9GXttDauxNFjECgYEAxw8W
			GXrA+9pvLhCJEWQO0rmzVDqOOip/0/Zc/3bhDm0BVwyAO8cBD4evONipbHs97K+C
			qeGNtCooHgFiuMbWXnBp7xU7BzWWg2m05vnKoipgl1Wv3CmOJvNMKCsxSkeAmVvU
			wgy9DMjOrr6gDeOA3bPhgvKsVyAd0caq3cUtzY8CgYAKAKJ0vVAoeqrsSwSTwCwP
			YLxm8fpNkYIQhCUbAtyEGtKnzrQETLgJSAmJ+sV4svwaRylrH/aa2CjQdMHjKTbQ
			ZsAmdx/CPemK7cxEAB6qaeBPEq4xVzdGSAq0mJPISKgnO0lB4N6Bks1wLZqScx9q
			lK9Iq90e8OZZJaLeB0VJ4QKBgEyCAud2I9hpI1RcLKKPh5b8ou1buDCpBwqL5UCX
			C3dE9D/l4R+YpFK4vuMlezDjyXBAIQN0WseFcHff5nbIjboskLrAuThTI2+M4Gfq
			WDSt/wQLfhUrr6RO3NWPjFlVVBsdjY+r37mbML0/LTtJVs1fhuySoyS1hmCLcD7e
			u3UHAoGALDKYStDYsW3CMyiUatjHNzGb7+M68ZR4du6fWCiRCwIlyLqVYhXZkm0P
			iXJJkT89DGZZuoV1pYTn9nnAd8RPAvzDpo/NsEpgucGxDAxdAkoM/lEjb5D1uW4q
			/4Q2tcGiVLgOS4/GZqBOF7mve+2UvG6nOlpOJuOEGvmuAIrxjoQ=
			-----END RSA PRIVATE KEY-----
			EOD
		),
		// VATSIM SSOv1 Authentication for development
		"dev" => array(
			"base" => "http://sso.hardern.net/server/",
			"key" => "SSO_DEMO_VACC",
			"secret" => "04i_~ruVUE.1-do1--sc",
			"method" => "RSA",
			"cert" => <<<EOD
			-----BEGIN RSA PRIVATE KEY-----
			MIIEpgIBAAKCAQEA2S5RckDw7SnEoZDmjaQHAQGajVlb7iwKIAX6nXbZBO7Uo3pN
			ItjmAbfkMqKBgWDVowM3UjbKivZNWGzkmxirArpbw9q7JhcX2LW6RfXx+5zn2+zW
			m58nQtnEgZtj8U9z3yjJEwfGbiJHEt56pNY0VFV5sDbEiQ52d/bPHlH17j/SUfm6
			eWCbUWW5S8kI8LDuN40qtxCZ0InTfRvcI3bx0+UBf9T6SYQWK2DsS+bz2YtKxVom
			Os9NdLbcPDK1rKPCJ+gvvmhCCt7jDbf1oFUzhPb6hjsIl1uRyjdtjhDb5FIokH+O
			3LuZdvSGF/SkoBnkfnqg5yTjC0GrnPg+Dr++1QIDAQABAoIBAQDIAisJwJrgnx2x
			+WMKQGwe1h5CXHAYOMCeW0NBLsmQDG8RmrldBUlVfcgPha8kukwlEvooocMIFOqI
			K8iguSgMnBmUlmTSIGRatIm2kljm8spotIWzze93VlvtTHDPM++vLb135CovFSxF
			SVTDZ23L2Of3i4iV/BbIRijacHq/jJ605OBcHhgW0ONCPUxL+uUd7siD68Y/BcYu
			km1OfQaxxryKdnE4UWzVKm0fwIzGvS/Baraek3kQCqOs7+OixV2YWFw6Xafq3WAp
			Pe5I/pJSevu90dGN01k84fVS6q3q419Z+VxarPYYznLrGGgUxM5zKlU4VHGwvA2p
			857ydg3hAoGBAPFuOulYQW8DIas4rlPPGofQI+dT0w8xf/YB1WmCtlt0GkSmEzd+
			JJZtcJiQSlTC4BuACvTBoIgo3vUC2wM5gZLz9NCeUHrwW0558q1YnGx1GNKcWgKK
			LrYvWPCrOKVnDvfhSQ4P3CPeUyks4OUTiPHY+5QlBpY7c1hSBnJWSNKZAoGBAOZJ
			dtle62ZK6S3TlIgbElaa1h8J5QyEFmcCPl47B4+SUNIljccO55OQhe89paMD2EH6
			Tbz9eP/s4U7X1tTb2onYtd7g3ldod/RBhrRHg7oXTmQj9wXopJsHwgNnYG59BPt2
			xpnB7aTmMZCXTO2YRxR4CCTtnOO/TZeNZV/xIK+dAoGBAJQ2sJHZ7WmiSYQcquCm
			jsn7nF8CFdsI715uJ767UQn5z7p/HeL+XKXAj9QJGKjKbdxUEeXKDKwqMx3E4AEt
			x38Ypx1/Yzbl4Zfew31pnbXzeQaql5Nhk2Wi0X4GDyNzjjvcoQWx9NpMPU9Uzsey
			42pdY6zBwjZuTtRUnsKId/JZAoGBALzXVXyfF85Ec76+mDicaodWZWwCgy+mSXCj
			KF3BbkvPojMR1Jd9o20gwJQVK3ToPDiud30ZJlZH++LZoDPhLe6IJWvlXq6y3lsQ
			ONQxKNY7Mm9wBqtzwTfYPsLnzO4N2z4Sgn2nx6bHlbGKQO09SFyCqbsOlu8z+v7i
			VlU8uJ8JAoGBAOmzlKBcEjJdlD0ZxkgMxp+YqpKkC+ojzf4tORn6jo2d/aKUOIAR
			bfRCMTmDmqyVoUH/SYgQWzD36zAy8HyHEz0U1k6+QMzWPbsEGQSQrk0DgnlOBPWo
			O0gQ0RDS3gD8C5XHvy5vryYjUOB10rUn9A2xLQw4sqKv2suHvIhc0Eit
			-----END RSA PRIVATE KEY-----
			EOD
		)
	)
);

?>