# WP-Mautic Form Integration
Plugin do WordPress de integração com formulários do Mautic.

### Instalação

Faça o download do arquivo zip e extraia. 

Copie a pasta **wp-mautic-form-integrator** para a pasta **wp-content/plugins/** na sua instalação do Wordpress.

Procure por **WP Mautic Form Integrator** no menu **Plugins** do seu Wordpress e ative-o.

### Configuração 

Será adicionado um novo menu no seu Wordpress chamado **WP Mautic Form Integrator**. Clique nele e vá em **Configuraçoes**.

A opção **Mautic Base URL** é o domínio em que seu Mautic está hospedado. O **OAuth Type** deve SEMPRE ser **OAuth2**.

Copie a **Mautic Redirect URI** indicada e vá para o seu Mautic. Lá, tenha certeza de estar com sua API ativada em **Configurações -> Configurações da API**.

Acesse o menu **Credenciais API** e gere credenciais do tipo **OAuth2** usando a **Mautic Redirect URI** copiada.

Após isso, você terá uma **Chave Pública** e uma **Chave Secreta**. Volte no Wordpress e insira elas nos campos correspondentes.

Selecione quais plugins utilizarão a integração, salve as alterações e está tudo pronto.

### Uso

No menu **WP Mautic Form Integrator** clique em **Adicionar Novo** e selecione qual plugin fará a integração com o Mautic.

Após isso é só selecionar os formulários e mapear cada campo.
