-- Meses de ejecución del gasto (ej. "1,3,5" = Enero, Marzo, Mayo)
USE new_ua;

ALTER TABLE gastos ADD COLUMN IF NOT EXISTS meses VARCHAR(150) NOT NULL DEFAULT '' AFTER valor_total;
