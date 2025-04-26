<?php
function morphLive(callable $controller, callable|null $middleware = null){
	return "/morph-live/".\Meract\Core\Morph::live($controller, $middleware);
}
