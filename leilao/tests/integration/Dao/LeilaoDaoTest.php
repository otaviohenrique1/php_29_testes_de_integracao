<?php

namespace Alura\Leilao\Tests\Integration\Domain;

use Alura\Leilao\Dao\Leilao as LeilaoDao;
// use Alura\Leilao\Infra\ConnectionCreator;
use Alura\Leilao\Model\Leilao;
use PDO;
use PHPUnit\Framework\TestCase;

class LeilaoDaoTest extends TestCase
{
  private static PDO $pdo;

  public static function setUpBeforeClass(): void
  {
    self::$pdo = new PDO('sqlite::memory:');
    // self::$pdo = ConnectionCreator::getConnection();
    self::$pdo->exec('CREATE TABLE leiloes (id INTEGER PRIMARY KEY, descricao TEXT, finalizado BOOL, dataInicio TEXT);');
  }

  public function setUp(): void {
    self::$pdo->beginTransaction();
  }

  public function testBuscaLeiloesNaoFinalizados()
  {
    // arrange
    $leilao = new Leilao('Variant 0Km');
    $leilaoDao = new LeilaoDao(self::$pdo);
    $leilaoDao->salva($leilao);

    $leilao = new Leilao('Fiat 147 0Km');
    $leilao->finaliza();
    $leilaoDao->salva($leilao);

    // act
    $leiloes = $leilaoDao->recuperarNaoFinalizados();
    
    // assert
    self::assertCount(1, $leiloes);
    self::assertContainsOnlyInstancesOf(Leilao::class, $leiloes);
    self::assertSame('Variant 0Km', $leiloes[0]->recuperarDescricao());
  }
  
  /**
   * @dataProvider leiloes
   */
  public function testBuscaLeiloesFinalizados(array $leiloes)
  {
    // arrange
    $leilaoDao = new LeilaoDao(self::$pdo);

    foreach ($leiloes as $leilao) {
      $leilaoDao->salva($leilao);
    }

    // act
    $leiloes = $leilaoDao->recuperarFinalizados();
    
    // assert
    self::assertCount(1, $leiloes);
    self::assertContainsOnlyInstancesOf(Leilao::class, $leiloes);
    self::assertSame('Fiat 147 0Km', $leiloes[0]->recuperarDescricao());
  }

  public function tearDown(): void {
    // Cancela a transacao
    self::$pdo->rollBack();
    // self::$pdo->exec('DELETE FROM leiloes;');
  }

  public static function leiloes() {
    $naoFinalizado = new Leilao('Variant 0Km');
    $finalizado = new Leilao('Fiat 147 0Km');
    $finalizado->finaliza();

    return [
      [
        [$naoFinalizado, $finalizado],
      ]
    ];
  }
}
