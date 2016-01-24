<?php
namespace Synoptic\Models;

class SourcesModel
{
    protected $db;

    public function __construct(\Doctrine\DBAL\Connection $dbo)
    {
        $this->db = $dbo;
    }

	//Получение всех локаций из БД
    public function getAll($limit = 50, $offset = 0)
    {	
		$rows = $this->db->fetchAll('SELECT id, name, y(sources.geom) as lat, x(sources.geom) as lon FROM sources');
        return $rows;
    }
	
	//Получение одной локации
	public function get($id)
    {
        $row = $this->db->fetchAssoc('SELECT sources.id, sources.name, y(sources.geom) as lat, x(sources.geom) as lon FROM sources WHERE id = ?', array($id));
		
		return $row;
    }

	//Добавление локации		
	public function add($data)
    {		
		$stmt = $this->db->prepare('INSERT INTO sources SET name = :name, geom = GeomFromWKB(POINT(:lat, :lon))');
		$stmt->bindValue('name', $data['name']);
		$stmt->bindValue('lat', $data['lat']);
		$stmt->bindValue('lon', $data['lon']);
		
		$result = $stmt->execute();
		
		if(!$result) return false;
		
		$new_id = $this->db->lastInsertId();
		
		return $new_id;
    }

	//Изменение локации		
	public function update($id, $data)
    {	
		
		$row_count = $this->db->fetchColumn('SELECT COUNT(*) FROM sources WHERE id = ?', array($id));
		
		if($row_count == 0) return false;
		
		$stmt = $this->db->prepare('UPDATE sources SET name = :name, geom = GeomFromWKB(POINT(:lat, :lon)) WHERE id = :id');
		$stmt->bindValue('name', $data['name']);
		$stmt->bindValue('lat', $data['lat']);
		$stmt->bindValue('lon', $data['lon']);
		$stmt->bindValue('id', $id);
		
		$result = $stmt->execute();
	
		return $result;
    }
	
	//Удаление локации	
	public function delete($id)
    {
        $result = $this->db->delete('sources', array('id' => $id));
	
		return $result;
    }
}