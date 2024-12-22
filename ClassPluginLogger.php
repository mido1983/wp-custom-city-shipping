<?php
if (!defined('ABSPATH')) {
    exit; // Запрет прямого доступа
}


class PluginLogger {
    private $log_file;
    public function __construct($log_file_name = 'plugin.log') {
        // Определяем путь к папке плагина
        $plugin_dir = plugin_dir_path(__FILE__);

        // Устанавливаем полный путь к файлу лога
        $this->log_file = $plugin_dir . $log_file_name;

        // Проверяем существование файла лога
        if (!file_exists($this->log_file)) {
            // Создаём файл лога, если его нет
            file_put_contents($this->log_file, "Log file created on " . date('Y-m-d H:i:s') . "\n");
        }
    }

    /**
     * Записывает сообщение в лог
     *
     * @param string $message Сообщение для записи
     * @param string $level Уровень лога (например, INFO, ERROR)
     */
    public function log($message, $level = 'INFO') {
        $time_stamp = date('Y-m-d H:i:s');
        $log_entry = "[$time_stamp] [$level] $message\n";

        // Записываем сообщение в файл лога
        file_put_contents($this->log_file, $log_entry, FILE_APPEND);
    }
}

// Пример использования
//$logger = new PluginLogger();
//$logger->log('Plugin initialized');
//$logger->log('An error occurred', 'ERROR');

