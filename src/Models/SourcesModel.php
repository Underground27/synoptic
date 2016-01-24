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
	
	//Получение ближайшего источника в радиусе, 100 км
	public function getNearestSource($lon, $lat){
		return $this->getNearestSources($lon, $lat, 1);
	}
	
	//Получение ближайших источников в радиусе, 100 км
    public function getNearestSources($lon, $lat, $limit = 100, $offset = 0)
    {	
		//Получить координаты нижнего левого и верхнего правого углов квадрата поиска
		$dist = 100;				//Радиус поиска, км.
		$latdeg_len_km = 111.045;	//Длина градуса широты, км.

		$rlon_part = $dist/abs(cos(deg2rad($lat))*$latdeg_len_km);
		$rlat_part = $dist/$latdeg_len_km;
		
		$rlon1 = $lon-$rlon_part;
		$rlon2 = $lon+$rlon_part;
		$rlat1 = $lat-$rlat_part;
		$rlat2 = $lat+$rlat_part;
		
		//Запросить входящие в зону точки
		$stmt = $this->db->prepare('SELECT id, name FROM sources 
			WHERE st_within(geom, envelope(linestring(point(:rlon1, :rlat1), point(:rlon2, :rlat2))))
			ORDER BY st_distance(point(:lon, :lat), geom) 
			LIMIT :offset, :limit');		
		$stmt->bindValue('lon', $lon);
		$stmt->bindValue('lat', $lat);
		$stmt->bindValue('rlon1', $rlon1);
		$stmt->bindValue('rlon2', $rlon2);
		$stmt->bindValue('rlat1', $rlat1);
		$stmt->bindValue('rlat2', $rlat2);		
		$stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
		$stmt->bindValue('offset', $offset, \PDO::PARAM_INT);
		
		$stmt->execute();
		$result = $stmt->fetchAll();
		
		if(!$result) return null;
		
		//Если была запрошена одна точка - вернуть ее ID
		if($limit == 1) return $result[0]['id'];
        
		return $result;
    }

	//Добавление локации		
	public function add($data)
    {		
		$stmt = $this->db->prepare('INSERT INTO sources SET name = :name, geom = GeomFromWKB(POINT(:lon, :lat))');
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
		
		$stmt = $this->db->prepare('UPDATE sources SET name = :name, geom = GeomFromWKB(POINT(:lon, :lat)) WHERE id = :id');
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