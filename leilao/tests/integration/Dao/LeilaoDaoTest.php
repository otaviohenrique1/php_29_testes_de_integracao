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

  public function setUp(): void
  {
    self::$pdo->beginTransaction();
  }

  /**
   * @dataProvider leiloes
   */
  public function testBuscaLeiloesNaoFinalizados(array $leiloes)
  {
    // arrange
    $leilaoDao = new LeilaoDao(self::$pdo);

    foreach ($leiloes as $leilao) {
      $leilaoDao->salva($leilao);
    }

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
    self::assertTrue($leiloes[0]->estaFinalizado());
  }

  public function testAoAtualizarLeilaoStatusDeveSerAlterado()
  {
    $leilao = new Leilao('Brasilia Amarela');
    $leilaoDao = new LeilaoDao(self::$pdo);
    $leilao = $leilaoDao->salva($leilao);

    $leiloes = $leilaoDao->recuperarNaoFinalizados();
    self::assertCount(1, $leiloes);
    self::assertSame('Brasilia Amarela', $leiloes[0]->recuperarDescricao());
    self::assertFalse($leiloes[0]->estaFinalizado());

    $leilao->finaliza();

    $leilaoDao->atualiza($leilao);

    $leiloes = $leilaoDao->recuperarFinalizados();
    self::assertCount(1, $leiloes);
    self::assertSame('Brasilia Amarela', $leiloes[0]->recuperarDescricao());
    self::assertTrue($leiloes[0]->estaFinalizado());
  }

  public function tearDown(): void
  {
    // Cancela a transacao
    self::$pdo->rollBack();
    // self::$pdo->exec('DELETE FROM leiloes;');
  }

  public static function leiloes()
  {
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
