<?php
/**
 * Clase Validator
 * Reglas comunes de validación para el sistema
 */
class Validator {
	public static function nombreValido($texto) {
		return (bool) preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s\'-]+$/u', $texto);
	}

	public static function cedulaValida($texto) {
		return (bool) preg_match('/^[0-9\s-]+$/', $texto);
	}

	public static function telefonoValido($texto) {
		return (bool) preg_match('/^[0-9\s+\-()]+$/', $texto);
	}

	public static function esMayorDeEdad($fechaNacimiento, $edadMinima = 18) {
		if (empty($fechaNacimiento)) {
			return false;
		}

		$fecha = DateTime::createFromFormat('Y-m-d', $fechaNacimiento);
		if (!$fecha || $fecha->format('Y-m-d') !== $fechaNacimiento) {
			return false;
		}

		$hoy = new DateTime('today');
		if ($fecha > $hoy) {
			return false;
		}

		$edad = $fecha->diff($hoy)->y;
		return $edad >= $edadMinima;
	}
}
?>
