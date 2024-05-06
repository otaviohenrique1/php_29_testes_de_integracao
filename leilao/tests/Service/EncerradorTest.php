<?php

namespace Alura\Leilao\Tests\Service;

use Alura\Leilao\Dao\Leilao as LeilaoDao;
use Alura\Leilao\Model\Leilao;
use Alura\Leilao\Service\Encerrador;
use Alura\Leilao\Service\EnviadorEmail;
use DateTimeImmutable;
use DomainException;
use PHPUnit\Framework\MockObject\MockObject;
// use PDO;
use PHPUnit\Framework\TestCase;

class Encerradortest extends TestCase
{
  private $encerrador;
  // /** @var MockObject */
  private $enviadorEmail;
  private $leilaoFiat147;
  private $leilaoVariant;

  public function setUp(): void
  {
    $this->leilaoFiat147 = new Leilao(
      'Fiat 147 0Km',
      new DateTimeImmutable('8 days ago'),
    );

    $this->leilaoVariant = new Leilao(
      'Variant 1972 0Km',
      new DateTimeImmutable('10 days ago'),
    );

    $leilaoDao = $this->createMock(LeilaoDao::class);
    $leilaoDao->method('recuperarNaoFinalizados')->willReturn([$this->leilaoFiat147, $this->leilaoVariant]);
    $leilaoDao->expects($this->exactly(2))->method('atualiza');

    $this->enviadorEmail = $this->createMock(EnviadorEmail::class);

    $this->encerrador = new Encerrador($leilaoDao, $this->enviadorEmail);
  }

  public function testLeiloesComMaisDeUmaSemanaDevemSerEncerrados()
  {
    $this->encerrador->encerra();
    $leiloes = [$this->leilaoFiat147, $this->leilaoVariant];
    self::assertCount(2, $leiloes);
    self::assertTrue($leiloes[0]->estaFinalizado());
    self::assertTrue($leiloes[1]->estaFinalizado());
  }

  public function testeDeveContinuarOProcessamentoAoEncontrarErroAoEnviarEmail()
  {
    $e = new DomainException('Erro ao enviar e-mail');
    $this->enviadorEmail->expects($this->exactly(2))
      ->method('notificarTerminoLeilao')
      ->willThrowException($e);
    $this->encerrador->encerra();
  }

  public function testSoDeveEnviarLeilaoPorEmailAposFinalizado()
  {
    $this->enviadorEmail
      ->expects($this->exactly(2))
      ->method('notificarTerminoLeilao')
      ->willReturnCallback(function (Leilao $leilao) {
        static::assertTrue($leilao->estaFinalizado());
      });
    $this->encerrador->encerra();
  }
}

class LeilaoDaoMock extends LeilaoDao
{
  private $leiloes = [];

  public function salva(Leilao $leilao): void
  {
    $this->leiloes[] = $leilao;
  }

  public function recuperarNaoFinalizados(): array
  {
    return array_filter($this->leiloes, function (Leilao $leilao) {
      return !$leilao->estaFinalizado();
    });
  }

  public function recuperarFinalizados(): array
  {
    return array_filter($this->leiloes, function (Leilao $leilao) {
      return $leilao->estaFinalizado();
    });
  }

  public function atualiza(Leilao $leilao)
  {
  }
}
