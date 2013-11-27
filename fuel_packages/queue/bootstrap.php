<?php

Autoloader::add_core_namespace('Queue');
Autoloader::add_classes(array(
	'Queue\\Queue' => __DIR__ . '/classes/queue.php',
	'Queue\\QueueDataInterface' => __DIR__ . '/classes/queue.php',
	'Queue\\QueueDataResultInterface' => __DIR__ . '/classes/queue.php',
	'Queue\\QueueStoreInterface' => __DIR__ . '/classes/queue.php',
	'Queue\\QueueConsumerInterface' => __DIR__ . '/classes/queue.php',
	'Queue\\Handler\\Notify_Handler'=> __DIR__ . '/classes/handler/notify_handler.php',
	'Queue\\Consumer\\Notify_Consumer'=> __DIR__ . '/classes/consumer/notify_consumer.php',
	'Queue\\Util\\Queue_Container'=> __DIR__ . '/classes/util/queue_container.php',
));
