<?php /* Compilé par CoreliaTemplate, ne pas éditer */ ?>
<!-- /src/Views/base.ctpl -->

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>CoreliaPHP - Bienvenue sur votre nouveau Framework !</title>
    <meta name="description" content="Framework PHP modulaire, rapide et extensible pour vos projets web.">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="index, follow">
    
	
	<style>

		body {
			margin: 0; 
			font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
			background: #f0f2f5; 
			box-sizing: border-box;
			color: #333;
		}

		.layout {
			display: flex;
			min-height: 100vh;
		}

		/* Sidebar */
		.sidebar {
			position: fixed;
			top: 0;
			left: 0;
			height: 100vh;
			min-width: 300px;
			width: 300px;
			background-color: #1f2937;
			color: #e0e7ff;
			padding: 30px 10px;
			box-sizing: border-box;
			display: flex;
			flex-direction: column;
			box-shadow: 2px 0 6px rgb(0 0 0 / 0.15);
			overflow-y: auto;
			z-index: 1000;
		}
		
		.sidebar h2 {
			margin-top: 0;
			font-weight: 700;
			font-size: 1.6rem;
			margin-bottom: 25px;
			border-bottom: 2px solid #4f46e5;
			padding-bottom: 12px;
			letter-spacing: 1px;
			display: flex;
			align-items: center;
			gap: 10px;
		}

		.sidebar h2 span.emoji {
			font-size: 28px;
		}
		
		.sidebar a {
			display: flex;
			flex-direction: row-reverse;
			align-items: center;
			justify-content: space-between;
			padding: 12px 20px;
			font-size: 16px;
			color: #c7c7c7;
			text-decoration: none;
			border-left: 4px solid transparent;
			transition: color 0.3s ease, border-color 0.3s ease, background-color 0.2s ease;
			margin-bottom: 14px;
			border-radius: 4px 0 0 4px;
			background-color: transparent;
			cursor: pointer;
			gap: 10px;
		}

		.sidebar a.active {
			color: #4f46e5;
			border-left-color: #4f46e5;
			background-color: #f0f0ff;
		}

		.sidebar a:not(.active):hover {
			color: #FFFFFF;
			background: rgba( 0, 0, 0, 0.2 );
			border-left-color: #4f46e5;
		}
		
		.sidebar a span.emoji {
			font-size: 20px;
			margin-top: 5px;
		}

		/* Main content */
		.main-content {
			flex: 1;
			padding: 30px 30px 40px;
			padding-left: 340px;
			box-sizing: border-box;
			background: white;
			display: flex;
			flex-direction: column;
			gap: 20px;
		}

		h1 {
			font-size: 2.4rem;
			color: #1f2937;
			margin-bottom: 10px;
		}

		p {
			font-size: 1.1rem;
			color: #4b5563;
			line-height: 1.5;
		}

		pre {
			background-color: #f3f4f6;
			border-radius: 8px;
			padding: 20px;
			font-family: 'Courier New', Courier, monospace;
			font-size: 1rem;
			overflow-x: auto;
			color: #1e293b;
			box-shadow: inset 0 0 5px rgb(0 0 0 / 0.1);
		}

		code {
			font-weight: 700;
			color: #4f46e5;
		}

		/* Responsive */
		@media (max-width: 900px) {
			.sidebar {
			display: none;
			}
			.main-content {
			padding-left: 20px;
			padding-right: 20px;
			}
		}

	</style>


</head>
<body>
    

	<div class="layout">

		<aside class="sidebar">

			<h2>
				<span class="emoji">🚀</span> 
				Corelia
			</h2>

			
			<a href="/" class="active">
				<span class="emoji">🏠</span>
				Accueil
			</a>

			
			<a href="/admin">
				<span class="emoji">🛠️</span>
				Administration
			</a>

			
			<a href="#">
				<span class="emoji">📚</span>
				Documentation
			</a>

		</aside>

		<main class="main-content">
			<h1>Bienvenue dans 🚀CoreliaPHP Framework</h1>
			<p>Félicitations, votre installation du framework CoreliaPHP est valide et prête à l’emploi ! 🎉</p>
		</main>

	</div>


</body>
</html>
