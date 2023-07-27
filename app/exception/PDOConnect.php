<?php

namespace app\exception;
use \PDO;
class PDOConnect
{

    private $pdo;

    public function __construct($config=[])
    {
        // 尝试连接到数据库
        try {
            $this->pdo = new PDO(
                'mysql:host=' . $config['host'] . ';dbname=' . $config['db'],
                $config['username'],
                $config['password']
            );
            // 设置PDO错误模式为异常
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            // 连接失败，输出错误信息
            die('连接数据库失败: ' . $e->getMessage());
        }
    }

    // 查询操作
    public function query($sql, $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die('查询失败: ' . $e->getMessage());
        }
    }

    // 插入操作
    public function insert($sql, $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            die('插入失败: ' . $e->getMessage());
        }
    }

    // 更新操作
    public function update($sql, $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            die('更新失败: ' . $e->getMessage());
        }
    }

    // 删除操作
    public function delete($sql, $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            die('删除失败: ' . $e->getMessage());
        }
    }


    public function closeConnect(){
        $this->pdo->closeCursor();
    }
}






