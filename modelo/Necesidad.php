<?php

require_once __DIR__ . '/../config/conexion.php';

class Necesidad
{
    private PDO $db;

    private const SELECT_CON_JOINS = "SELECT n.*, u.nombre AS nombre_solicitante,
                li.nombre AS linea_inversion_nombre, li.descripcion AS linea_inversion_descripcion,
                sli.nombre AS sublinea_inversion_nombre, sli.descripcion AS sublinea_inversion_descripcion,
                s.codigo AS sede_codigo, s.nombre AS sede_nombre,
                p.codigo AS proyecto_codigo, p.nombre AS proyecto_nombre,
                e.nombre AS estamento_solicitante_nombre,
                ru.nombre AS responsable_nombre
             FROM necesidades_academicas n
             JOIN usuarios u ON u.id = n.usuario_id
             LEFT JOIN lineas_inversion li ON li.codigo = n.linea_inversion
             LEFT JOIN sublineas_inversion sli ON sli.codigo = n.sublinea_inversion
             LEFT JOIN sedes s ON s.id = n.sede_id
             LEFT JOIN proyectos p ON p.id = n.proyecto_pdi_id
             LEFT JOIN estamentos e ON e.id = n.estamento_solicitante_id
             LEFT JOIN usuarios ru ON ru.id = n.responsable_usuario_id";

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function crear(array $datos, int $usuarioId): int
    {
        $consulta = $this->db->prepare(
            'INSERT INTO necesidades_academicas
                (usuario_id, vigencia, nombre_necesidad, descripcion, justificacion, estamento_solicitante_id,
                 beneficiarios_cantidad, linea_inversion, sublinea_inversion, detalle_inversion, sede_id, dependencia,
                 programa_academico, proyecto_pdi_id, articulacion_plan, espacio_intervenir, requisitos_normativos,
                 valor, fuente_financiacion, responsable_usuario_id, observaciones)
             VALUES
                (:usuario_id, :vigencia, :nombre_necesidad, :descripcion, :justificacion, :estamento_solicitante_id,
                 :beneficiarios_cantidad, :linea_inversion, :sublinea_inversion, :detalle_inversion, :sede_id, :dependencia,
                 :programa_academico, :proyecto_pdi_id, :articulacion_plan, :espacio_intervenir, :requisitos_normativos,
                 :valor, :fuente_financiacion, :responsable_usuario_id, :observaciones)'
        );

        $consulta->execute([
            'usuario_id' => $usuarioId,
            'vigencia' => $datos['vigencia'],
            'nombre_necesidad' => $datos['nombre_necesidad'],
            'descripcion' => $datos['descripcion'] ?: null,
            'justificacion' => $datos['justificacion'] ?: null,
            'estamento_solicitante_id' => $datos['estamento_solicitante_id'] ?: null,
            'beneficiarios_cantidad' => $datos['beneficiarios_cantidad'] !== '' ? $datos['beneficiarios_cantidad'] : null,
            'linea_inversion' => $datos['linea_inversion'],
            'sublinea_inversion' => $datos['sublinea_inversion'],
            'detalle_inversion' => $datos['detalle_inversion'] ?: null,
            'sede_id' => $datos['sede_id'],
            'dependencia' => $datos['dependencia'],
            'programa_academico' => $datos['programa_academico'] ?: null,
            'proyecto_pdi_id' => $datos['proyecto_pdi_id'] ?: null,
            'articulacion_plan' => $datos['articulacion_plan'] ?: null,
            'espacio_intervenir' => $datos['espacio_intervenir'] ?: null,
            'requisitos_normativos' => $datos['requisitos_normativos'] ?: null,
            'valor' => $datos['valor'],
            'fuente_financiacion' => $datos['fuente_financiacion'],
            'responsable_usuario_id' => $datos['responsable_usuario_id'],
            'observaciones' => $datos['observaciones'] ?: null,
        ]);

        $necesidadId = (int) $this->db->lastInsertId();

        $this->guardarBeneficiariosEstamentos($necesidadId, $datos['beneficiarios_estamentos'] ?? []);

        return $necesidadId;
    }

    public function actualizar(int $id, array $datos): bool
    {
        $consulta = $this->db->prepare(
            'UPDATE necesidades_academicas SET
                vigencia = :vigencia, nombre_necesidad = :nombre_necesidad, descripcion = :descripcion,
                justificacion = :justificacion, estamento_solicitante_id = :estamento_solicitante_id,
                beneficiarios_cantidad = :beneficiarios_cantidad, linea_inversion = :linea_inversion,
                sublinea_inversion = :sublinea_inversion, detalle_inversion = :detalle_inversion,
                sede_id = :sede_id, dependencia = :dependencia, programa_academico = :programa_academico,
                proyecto_pdi_id = :proyecto_pdi_id, articulacion_plan = :articulacion_plan,
                espacio_intervenir = :espacio_intervenir, requisitos_normativos = :requisitos_normativos,
                valor = :valor, fuente_financiacion = :fuente_financiacion,
                responsable_usuario_id = :responsable_usuario_id, observaciones = :observaciones
             WHERE id = :id'
        );

        $resultado = $consulta->execute([
            'id' => $id,
            'vigencia' => $datos['vigencia'],
            'nombre_necesidad' => $datos['nombre_necesidad'],
            'descripcion' => $datos['descripcion'] ?: null,
            'justificacion' => $datos['justificacion'] ?: null,
            'estamento_solicitante_id' => $datos['estamento_solicitante_id'] ?: null,
            'beneficiarios_cantidad' => $datos['beneficiarios_cantidad'] !== '' ? $datos['beneficiarios_cantidad'] : null,
            'linea_inversion' => $datos['linea_inversion'],
            'sublinea_inversion' => $datos['sublinea_inversion'],
            'detalle_inversion' => $datos['detalle_inversion'] ?: null,
            'sede_id' => $datos['sede_id'],
            'dependencia' => $datos['dependencia'],
            'programa_academico' => $datos['programa_academico'] ?: null,
            'proyecto_pdi_id' => $datos['proyecto_pdi_id'] ?: null,
            'articulacion_plan' => $datos['articulacion_plan'] ?: null,
            'espacio_intervenir' => $datos['espacio_intervenir'] ?: null,
            'requisitos_normativos' => $datos['requisitos_normativos'] ?: null,
            'valor' => $datos['valor'],
            'fuente_financiacion' => $datos['fuente_financiacion'],
            'responsable_usuario_id' => $datos['responsable_usuario_id'],
            'observaciones' => $datos['observaciones'] ?: null,
        ]);

        $this->guardarBeneficiariosEstamentos($id, $datos['beneficiarios_estamentos'] ?? []);

        return $resultado;
    }

    private function guardarBeneficiariosEstamentos(int $necesidadId, array $estamentoIds): void
    {
        $this->db->prepare('DELETE FROM necesidad_beneficiarios_estamentos WHERE necesidad_id = :necesidad_id')
            ->execute(['necesidad_id' => $necesidadId]);

        if (empty($estamentoIds)) {
            return;
        }

        $consulta = $this->db->prepare(
            'INSERT IGNORE INTO necesidad_beneficiarios_estamentos (necesidad_id, estamento_id) VALUES (:necesidad_id, :estamento_id)'
        );

        foreach (array_unique(array_map('intval', $estamentoIds)) as $estamentoId) {
            if ($estamentoId <= 0) {
                continue;
            }

            $consulta->execute(['necesidad_id' => $necesidadId, 'estamento_id' => $estamentoId]);
        }
    }

    public function obtenerBeneficiariosEstamentos(int $necesidadId): array
    {
        $consulta = $this->db->prepare(
            'SELECT e.id, e.nombre
             FROM necesidad_beneficiarios_estamentos nbe
             JOIN estamentos e ON e.id = nbe.estamento_id
             WHERE nbe.necesidad_id = :necesidad_id
             ORDER BY e.nombre'
        );
        $consulta->execute(['necesidad_id' => $necesidadId]);

        return $consulta->fetchAll();
    }

    private function adjuntarBeneficiarios(array $filas): array
    {
        foreach ($filas as &$fila) {
            $fila['beneficiarios_estamentos'] = $this->obtenerBeneficiariosEstamentos((int) $fila['id']);
        }

        return $filas;
    }

    public function obtenerPorUsuario(int $usuarioId): array
    {
        $consulta = $this->db->prepare(
            self::SELECT_CON_JOINS . ' WHERE n.usuario_id = :usuario_id ORDER BY n.creado_en DESC'
        );
        $consulta->execute(['usuario_id' => $usuarioId]);

        return $this->adjuntarBeneficiarios($consulta->fetchAll());
    }

    public function obtenerTodas(): array
    {
        $consulta = $this->db->prepare(self::SELECT_CON_JOINS . ' ORDER BY n.creado_en DESC');
        $consulta->execute();

        return $this->adjuntarBeneficiarios($consulta->fetchAll());
    }

    public function obtenerPorId(int $id): ?array
    {
        $consulta = $this->db->prepare(self::SELECT_CON_JOINS . ' WHERE n.id = :id');
        $consulta->execute(['id' => $id]);
        $fila = $consulta->fetch();

        if ($fila === false) {
            return null;
        }

        $fila['beneficiarios_estamentos'] = $this->obtenerBeneficiariosEstamentos($id);

        return $fila;
    }

    public function obtenerEnviadas(): array
    {
        $consulta = $this->db->prepare(self::SELECT_CON_JOINS . " WHERE n.estado = 'enviado' ORDER BY n.creado_en DESC");
        $consulta->execute();

        return $this->adjuntarBeneficiarios($consulta->fetchAll());
    }

    public function enviarTodosBorrador(string $dependenciaDestinoNombre, int $rolDestinatarioId): int
    {
        $consulta = $this->db->prepare(
            "UPDATE necesidades_academicas
             SET estado = 'enviado', rol_destinatario_id = :rol_destinatario_id, dependencia_destino = :dependencia
             WHERE estado = 'borrador'"
        );
        $consulta->execute([
            'dependencia' => $dependenciaDestinoNombre,
            'rol_destinatario_id' => $rolDestinatarioId,
        ]);

        return $consulta->rowCount();
    }

    public function eliminar(int $id): bool
    {
        $consulta = $this->db->prepare('DELETE FROM necesidades_academicas WHERE id = :id');

        return $consulta->execute(['id' => $id]);
    }

    public function obtenerRecientes(int $limite = 5): array
    {
        $consulta = $this->db->prepare(self::SELECT_CON_JOINS . ' ORDER BY n.creado_en DESC LIMIT :limite');
        $consulta->bindValue('limite', $limite, PDO::PARAM_INT);
        $consulta->execute();

        return $this->adjuntarBeneficiarios($consulta->fetchAll());
    }
}
