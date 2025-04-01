<?php
namespace Meract\Core;

/**
 * Класс для форматированного вывода переменных.
 *
 * Предоставляет методы для безопасного получения строкового представления переменных
 * с помощью функций var_dump() и print_r() без непосредственного вывода в буфер.
 */
class OUTVAR 
{
	/**
	 * Возвращает строковое представление переменной в формате var_dump().
	 *
	 * @param mixed $value Переменная для дампа
	 * @return string Результат работы var_dump()
	 */
	public static function dump(mixed $value): string 
	{
		ob_start();
		var_dump($value);
		$result = ob_get_contents();
		ob_end_clean();
		return $result;
	}

	/**
	 * Возвращает строковое представление переменной в формате print_r().
	 *
	 * @param mixed $value Переменная для вывода
	 * @return string Результат работы print_r()
	 */
	public static function print(mixed $value): string 
	{
		ob_start();
		print_r($value);
		$result = ob_get_contents();
		ob_end_clean();
		return $result;
	}
}
