<?php
/**
 * This file just hooks into our Router and renders the right controller.
 * WordPress needs a php file to route to, so we send it here.
 */
echo $_awful_instance->render();
