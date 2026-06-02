# Trabalho em Grupo – Evolução do Sistema MediAgenda

## Descrição da Aplicação
O MediAgenda é um sistema web desenvolvido em PHP para o agendamento de consultas médicas. Este projeto trata-se da evolução do código base desenvolvido em sala de aula para a disciplina de Programação II, visando a implementação de novos módulos de cadastro e a integração completa do sistema.

## Funcionalidades Implementadas
* **Módulo de Especialidades:** CRUD completo (Create, Read, Update, Delete/Inativar) para as especialidades médicas da clínica.
* **Módulo de Médicos:** CRUD completo para cadastro de médicos, incluindo o relacionamento (chave estrangeira) com as especialidades cadastradas no banco de dados.
* **Ajuste de Navegação:** Integração e roteamento correto dos links do menu lateral (`principal.php`) para os respectivos módulos do sistema.

## Tecnologias Utilizadas
* Backend: PHP
* Banco de Dados: MySQL / MariaDB
* Frontend: HTML5, CSS3, e Bootstrap
* Interatividade: JavaScript

## Instruções Básicas de Execução

Para rodar este projeto em sua máquina local, siga os passos abaixo:

1. Certifique-se de ter um ambiente de servidor local instalado (como XAMPP ou WAMP).
2. Clone este repositório para dentro da pasta pública do seu servidor (ex: `htdocs` no XAMPP).
3. Inicie os serviços do **Apache** e do **MySQL** no painel de controle do seu servidor local.
4. Acesse o gerenciador do banco de dados (ex: phpMyAdmin) e execute as instruções contidas no arquivo `script.sql` para criar as tabelas necessárias (`medicos` e `especialidades`).
5. Abra o navegador e acesse a aplicação através da URL: `http://localhost/Projeto-PHP` (ou o nome correspondente à pasta clonada).

## Integrantes do Grupo
* Eduardo Sant'Ana
* João Luís Pedrosa Teles
* João Victor Bonfim

---
*Desenvolvido como requisito de avaliação acadêmica.*