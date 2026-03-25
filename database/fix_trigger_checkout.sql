DROP TRIGGER IF EXISTS after_checkout_update;
DELIMITER $$
CREATE TRIGGER after_checkout_update
AFTER UPDATE ON RESERVA
FOR EACH ROW
BEGIN
    IF NEW.fecha_checkout IS NOT NULL AND OLD.fecha_checkout IS NULL THEN
        UPDATE HABITACION
        SET estado = 'disponible'
        WHERE habitacion_id = NEW.habitacion_id;
    END IF;
END$$
DELIMITER ;
