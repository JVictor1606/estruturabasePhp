<?php

declare(strict_types=1);

namespace Modular\Modules\Auditoria\Infrastructure;

use Modular\Core\Database;
use Modular\Modules\Auditoria\Domain\IAuditoriaRepository;
use PDO;

class AuditoriaRepository implements IAuditoriaRepository
{
    private PDO $conn;
    public function __construct(Database $conn)
    {
        $this->conn = $conn->getConnection();
    }
    public function create(array $data): void
    {
        $sql = 'INSERT INTO tb_logs_auditoria (usuario_id, modulo, acao, registro_id, detalhe, created_at) 
                VALUES (:usuario_id, :modulo, :acao, :registro_id, :detalhe, :created_at)';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':usuario_id', $data['usuario_id']);
        $stmt->bindValue(':modulo', $data['modulo']);
        $stmt->bindValue(':acao', $data['acao']);
        $stmt->bindValue(':registro_id', $data['registro_id']);
        $stmt->bindValue(':detalhe', json_encode($data['detalhe']));
        $stmt->bindValue(':created_at', (new \DateTimeImmutable())->format('Y-m-d H:i:s'));
        $stmt->execute();
    }

    public function getlastRegistros(int $limit): array
    {
        $sql =
            'SELECT id,registro_id,modulo,acao,created_at 
        FROM tb_logs_auditoria where modulo != "User" ORDER BY created_at DESC LIMIT :limit';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
