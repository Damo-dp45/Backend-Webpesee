<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260518154350 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE demande_solde (created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, created_by INT DEFAULT NULL, updated_by INT DEFAULT NULL, deleted_by INT DEFAULT NULL, id INT AUTO_INCREMENT NOT NULL, montantdemande INT NOT NULL, statut VARCHAR(255) NOT NULL, motif VARCHAR(255) DEFAULT NULL, traite_at DATETIME DEFAULT NULL, site_id INT NOT NULL, traite_par_id INT DEFAULT NULL, INDEX IDX_904F004EF6BD1646 (site_id), INDEX IDX_904F004E167FABE8 (traite_par_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE entreprise (created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, created_by INT DEFAULT NULL, updated_by INT DEFAULT NULL, deleted_by INT DEFAULT NULL, id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, adresse VARCHAR(255) DEFAULT NULL, contact1 VARCHAR(255) NOT NULL, contact2 VARCHAR(255) DEFAULT NULL, codeentreprise VARCHAR(255) NOT NULL, solde INT NOT NULL, statut VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE fournisseur (created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, created_by INT DEFAULT NULL, updated_by INT DEFAULT NULL, deleted_by INT DEFAULT NULL, id INT AUTO_INCREMENT NOT NULL, codefournisseur VARCHAR(255) NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, contact1 VARCHAR(10) NOT NULL, contact2 VARCHAR(10) DEFAULT NULL, prixspeciale INT DEFAULT NULL, statut VARCHAR(255) NOT NULL, site_id INT DEFAULT NULL, INDEX IDX_369ECA32F6BD1646 (site_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE mouvement_caisse (created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, created_by INT DEFAULT NULL, updated_by INT DEFAULT NULL, deleted_by INT DEFAULT NULL, id INT AUTO_INCREMENT NOT NULL, type VARCHAR(255) NOT NULL, montant INT NOT NULL, motif VARCHAR(255) DEFAULT NULL, site_id INT NOT NULL, paiement_id INT DEFAULT NULL, demande_solde_id INT DEFAULT NULL, INDEX IDX_C8E3DDFEF6BD1646 (site_id), INDEX IDX_C8E3DDFE2A4C4478 (paiement_id), INDEX IDX_C8E3DDFEFA26396B (demande_solde_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE operation (id INT AUTO_INCREMENT NOT NULL, codesecret VARCHAR(255) NOT NULL, mouvement VARCHAR(255) NOT NULL, client VARCHAR(255) NOT NULL, destination VARCHAR(255) NOT NULL, provenance VARCHAR(255) NOT NULL, transporteur VARCHAR(255) NOT NULL, chauffeur VARCHAR(255) NOT NULL, immatriculation VARCHAR(255) NOT NULL, remorque VARCHAR(255) DEFAULT NULL, poids1 INT NOT NULL, poids2 INT NOT NULL, poidsbrut INT NOT NULL, poidsnet INT NOT NULL, date1 DATETIME NOT NULL, date2 DATETIME NOT NULL, temps1 TIME NOT NULL, temps2 TIME NOT NULL, datesearch DATETIME NOT NULL, codepesee VARCHAR(255) NOT NULL, numticket VARCHAR(255) NOT NULL, code VARCHAR(255) NOT NULL, peseur VARCHAR(255) DEFAULT NULL, codesite VARCHAR(255) NOT NULL, libellesite VARCHAR(255) DEFAULT NULL, prixunitaire INT DEFAULT NULL, montantcalcule INT DEFAULT NULL, site_id INT NOT NULL, fournisseur_id INT DEFAULT NULL, produit_id INT DEFAULT NULL, INDEX IDX_1981A66DF6BD1646 (site_id), INDEX IDX_1981A66D670C757F (fournisseur_id), INDEX IDX_1981A66DF347EFB (produit_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE paiement (created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, created_by INT DEFAULT NULL, updated_by INT DEFAULT NULL, deleted_by INT DEFAULT NULL, id INT AUTO_INCREMENT NOT NULL, montant INT NOT NULL, modepaiement VARCHAR(255) NOT NULL, referencemobile VARCHAR(255) DEFAULT NULL, statut VARCHAR(255) NOT NULL, fournisseur_id INT DEFAULT NULL, site_id INT DEFAULT NULL, operation_id INT DEFAULT NULL, INDEX IDX_B1DC7A1E670C757F (fournisseur_id), INDEX IDX_B1DC7A1EF6BD1646 (site_id), INDEX IDX_B1DC7A1E44AC3583 (operation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE produit (created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, created_by INT DEFAULT NULL, updated_by INT DEFAULT NULL, deleted_by INT DEFAULT NULL, id INT AUTO_INCREMENT NOT NULL, codeproduit VARCHAR(255) DEFAULT NULL, libelle VARCHAR(255) NOT NULL, prix INT NOT NULL, site_id INT DEFAULT NULL, INDEX IDX_29A5EC27F6BD1646 (site_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE refresh_tokens (refresh_token VARCHAR(128) NOT NULL, username VARCHAR(255) NOT NULL, valid DATETIME NOT NULL, id INT AUTO_INCREMENT NOT NULL, UNIQUE INDEX UNIQ_9BACE7E1C74F2195 (refresh_token), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE site (created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, created_by INT DEFAULT NULL, updated_by INT DEFAULT NULL, deleted_by INT DEFAULT NULL, id INT AUTO_INCREMENT NOT NULL, codesite VARCHAR(255) NOT NULL, libellesite VARCHAR(255) NOT NULL, solde INT NOT NULL, statut VARCHAR(255) NOT NULL, entreprise_id INT DEFAULT NULL, operateur_id INT DEFAULT NULL, INDEX IDX_694309E4A4AEAFEA (entreprise_id), INDEX IDX_694309E43F192FC (operateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, statut VARCHAR(255) NOT NULL, entreprise_id INT DEFAULT NULL, INDEX IDX_8D93D649A4AEAFEA (entreprise_id), UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE demande_solde ADD CONSTRAINT FK_904F004EF6BD1646 FOREIGN KEY (site_id) REFERENCES site (id)');
        $this->addSql('ALTER TABLE demande_solde ADD CONSTRAINT FK_904F004E167FABE8 FOREIGN KEY (traite_par_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE fournisseur ADD CONSTRAINT FK_369ECA32F6BD1646 FOREIGN KEY (site_id) REFERENCES site (id)');
        $this->addSql('ALTER TABLE mouvement_caisse ADD CONSTRAINT FK_C8E3DDFEF6BD1646 FOREIGN KEY (site_id) REFERENCES site (id)');
        $this->addSql('ALTER TABLE mouvement_caisse ADD CONSTRAINT FK_C8E3DDFE2A4C4478 FOREIGN KEY (paiement_id) REFERENCES paiement (id)');
        $this->addSql('ALTER TABLE mouvement_caisse ADD CONSTRAINT FK_C8E3DDFEFA26396B FOREIGN KEY (demande_solde_id) REFERENCES demande_solde (id)');
        $this->addSql('ALTER TABLE operation ADD CONSTRAINT FK_1981A66DF6BD1646 FOREIGN KEY (site_id) REFERENCES site (id)');
        $this->addSql('ALTER TABLE operation ADD CONSTRAINT FK_1981A66D670C757F FOREIGN KEY (fournisseur_id) REFERENCES fournisseur (id)');
        $this->addSql('ALTER TABLE operation ADD CONSTRAINT FK_1981A66DF347EFB FOREIGN KEY (produit_id) REFERENCES produit (id)');
        $this->addSql('ALTER TABLE paiement ADD CONSTRAINT FK_B1DC7A1E670C757F FOREIGN KEY (fournisseur_id) REFERENCES fournisseur (id)');
        $this->addSql('ALTER TABLE paiement ADD CONSTRAINT FK_B1DC7A1EF6BD1646 FOREIGN KEY (site_id) REFERENCES site (id)');
        $this->addSql('ALTER TABLE paiement ADD CONSTRAINT FK_B1DC7A1E44AC3583 FOREIGN KEY (operation_id) REFERENCES operation (id)');
        $this->addSql('ALTER TABLE produit ADD CONSTRAINT FK_29A5EC27F6BD1646 FOREIGN KEY (site_id) REFERENCES site (id)');
        $this->addSql('ALTER TABLE site ADD CONSTRAINT FK_694309E4A4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES entreprise (id)');
        $this->addSql('ALTER TABLE site ADD CONSTRAINT FK_694309E43F192FC FOREIGN KEY (operateur_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_8D93D649A4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES entreprise (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE demande_solde DROP FOREIGN KEY FK_904F004EF6BD1646');
        $this->addSql('ALTER TABLE demande_solde DROP FOREIGN KEY FK_904F004E167FABE8');
        $this->addSql('ALTER TABLE fournisseur DROP FOREIGN KEY FK_369ECA32F6BD1646');
        $this->addSql('ALTER TABLE mouvement_caisse DROP FOREIGN KEY FK_C8E3DDFEF6BD1646');
        $this->addSql('ALTER TABLE mouvement_caisse DROP FOREIGN KEY FK_C8E3DDFE2A4C4478');
        $this->addSql('ALTER TABLE mouvement_caisse DROP FOREIGN KEY FK_C8E3DDFEFA26396B');
        $this->addSql('ALTER TABLE operation DROP FOREIGN KEY FK_1981A66DF6BD1646');
        $this->addSql('ALTER TABLE operation DROP FOREIGN KEY FK_1981A66D670C757F');
        $this->addSql('ALTER TABLE operation DROP FOREIGN KEY FK_1981A66DF347EFB');
        $this->addSql('ALTER TABLE paiement DROP FOREIGN KEY FK_B1DC7A1E670C757F');
        $this->addSql('ALTER TABLE paiement DROP FOREIGN KEY FK_B1DC7A1EF6BD1646');
        $this->addSql('ALTER TABLE paiement DROP FOREIGN KEY FK_B1DC7A1E44AC3583');
        $this->addSql('ALTER TABLE produit DROP FOREIGN KEY FK_29A5EC27F6BD1646');
        $this->addSql('ALTER TABLE site DROP FOREIGN KEY FK_694309E4A4AEAFEA');
        $this->addSql('ALTER TABLE site DROP FOREIGN KEY FK_694309E43F192FC');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D649A4AEAFEA');
        $this->addSql('DROP TABLE demande_solde');
        $this->addSql('DROP TABLE entreprise');
        $this->addSql('DROP TABLE fournisseur');
        $this->addSql('DROP TABLE mouvement_caisse');
        $this->addSql('DROP TABLE operation');
        $this->addSql('DROP TABLE paiement');
        $this->addSql('DROP TABLE produit');
        $this->addSql('DROP TABLE refresh_tokens');
        $this->addSql('DROP TABLE site');
        $this->addSql('DROP TABLE `user`');
    }
}
