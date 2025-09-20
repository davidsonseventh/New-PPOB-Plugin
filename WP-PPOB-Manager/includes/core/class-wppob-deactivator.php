<?php
defined('ABSPATH') || exit;

class WPPOB_Deactivator {
    public static function deactivate() {
        flush_rewrite_rules();
    }
}
