<?php

$step   = $_SESSION["Get_pwd_step"] ?? 1;
$step   = intval($_SESSION["Get_pwd_step"]);
$step   = ($step > 3 || $step < 1) ? 1 : $step;

include get_template_directory().'/user/login/forget/forget-'.$step.'.php';