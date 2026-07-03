<?php

namespace Database\Seeders;

use App\Models\Challenge;
use App\Models\Team;
use Illuminate\Database\Seeder;

class ChallengeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rood = Team::query()->where('name', 'Rood')->firstOrFail();
        $blauw = Team::query()->where('name', 'Blauw')->firstOrFail();

        $challenges = [
            ['number' => 1, 'category' => 'Eten & drinken', 'points' => 10, 'title' => 'McDrive te voet', 'description' => 'Bestel en haal een compleet menu op bij de McDrive — lopend of fietsend, niet met de auto. Foto bij het loket.'],
            ['number' => 2, 'category' => 'Eten & drinken', 'points' => 10, 'title' => 'Onbekend biertje', 'description' => 'Koop bij de plaatselijke supermarkt of kroeg een bier dat niemand kent, proef het samen en geef live een rapportcijfer.'],
            ['number' => 3, 'category' => 'Eten & drinken', 'points' => 15, 'title' => 'Sauerlandse snack', 'description' => 'Zoek een typisch Duitse/regionale snack op (bijv. Bratwurst) en film ieders eerste reactie bij de eerste hap.'],
            ['number' => 4, 'category' => 'Eten & drinken', 'points' => 5, 'title' => 'Geen handen', 'description' => 'Eet een compleet tussendoortje zonder je handen te gebruiken — op video.'],

            ['number' => 5, 'category' => 'Sportief & buiten', 'points' => 15, 'title' => 'Bergtop selfie', 'description' => 'Beklim de dichtstbijzijnde uitkijktoren of heuveltop en maak daar een groepsfoto met uitzicht.'],
            ['number' => 6, 'category' => 'Sportief & buiten', 'points' => 10, 'title' => 'Blinde gids', 'description' => 'Eén teamlid wordt geblinddoekt en alleen met verbale aanwijzingen door de rest een klein parcours geleid.'],
            ['number' => 7, 'category' => 'Sportief & buiten', 'points' => 10, 'title' => 'Bank af', 'description' => 'Rol iemand veilig een glooiend grasveld af — getimed en gefilmd.'],
            ['number' => 8, 'category' => 'Sportief & buiten', 'points' => 15, 'title' => 'Zaklamptikkertje', 'description' => "Speel 's avonds 10 minuten verstoppertje met zaklampen tussen beide teams — winnaar filmt de beslissende tik."],

            ['number' => 9, 'category' => 'Sociaal', 'points' => 10, 'title' => 'Handtekeningenjacht', 'description' => 'Verzamel de handtekening van drie wildvreemden op een blaadje, met foto van het moment zelf.'],
            ['number' => 10, 'category' => 'Sociaal', 'points' => 15, 'title' => 'Straatzanger', 'description' => 'Zing gezamenlijk een couplet voor een winkelmedewerker of voorbijganger — gefilmd.'],
            ['number' => 11, 'category' => 'Sociaal', 'points' => 20, 'title' => 'Drie keer ruilen', 'description' => 'Begin met een klein voorwerp en ruil het bij vreemden drie keer achter elkaar voor iets anders. Foto van elke ruil.'],
            ['number' => 12, 'category' => 'Sociaal', 'points' => 10, 'title' => 'Woordje Duits', 'description' => 'Leer een lokale een Duitse zin of dialectwoord en herhaal \'m correct op camera.'],
            ['number' => 13, 'category' => 'Sociaal', 'points' => 15, 'title' => 'Verkooppraatje', 'description' => 'Verkoop binnen 5 minuten (symbolisch, geen geld nodig) een voorwerp uit het vakantiehuis aan een vreemde — gefilmde pitch.'],

            ['number' => 14, 'category' => 'Creatief & in huis', 'points' => 10, 'title' => 'Twijgentoren', 'description' => 'Bouw met alleen materiaal dat je buiten vindt een toren die minimaal 1 meter hoog blijft staan — 10 seconden vastgehouden op camera.'],
            ['number' => 15, 'category' => 'Creatief & in huis', 'points' => 15, 'title' => 'Levend standbeeld', 'description' => 'Twee teamleden staan 5 minuten doodstil geposeerd op een opvallende plek, gefilmd door de rest.'],
            ['number' => 16, 'category' => 'Creatief & in huis', 'points' => 5, 'title' => 'Linkshandig portret', 'description' => 'Teken met je niet-dominante hand in 3 minuten een portret van een teamgenoot; hij raadt zelf wie het is.'],
            ['number' => 17, 'category' => 'Creatief & in huis', 'points' => 10, 'title' => 'Recordpoging opruimen', 'description' => 'Ruim de keuken of huiskamer binnen 5 minuten spik en span op — voor- en na-foto.'],
            ['number' => 18, 'category' => 'Creatief & in huis', 'points' => 15, 'title' => 'Groepschoreografie', 'description' => 'Bedenk in 15 minuten een dansroutine op een zelfgekozen nummer en voer \'m gezamenlijk uit in één take.'],

            ['number' => 19, 'category' => 'Tussen de teams', 'points' => 10, 'title' => 'Verrassingsontbijt', 'description' => 'Bereid voor het andere team een ontbijtje en serveer het met een oprecht compliment — gefilmd.'],
            ['number' => 20, 'category' => 'Tussen de teams', 'points' => 10, 'title' => 'Pokerface', 'description' => 'Elk teamlid vertelt om de beurt een overduidelijke leugen met stalen gezicht; het andere team raadt wie loog.'],

            ['number' => 21, 'category' => 'Nacht & verrassing', 'points' => 20, 'title' => 'Middernachtmars', 'description' => 'Tussen 00:00–02:00 loopt het hele team naar een vooraf aangewezen punt in de buurt en fotografeert daar iets unieks.'],
            ['number' => 22, 'category' => 'Nacht & verrassing', 'points' => 25, 'title' => 'Geheime bonusopdracht A', 'description' => 'Wordt willekeurig alleen naar Team Rood gestuurd — Team Blauw weet niet dat \'m bestaat.', 'is_secret' => true, 'target_team_id' => $rood->id],
            ['number' => 23, 'category' => 'Nacht & verrassing', 'points' => 25, 'title' => 'Geheime bonusopdracht B', 'description' => 'Wordt willekeurig alleen naar Team Blauw gestuurd — Team Rood weet niet dat \'m bestaat.', 'is_secret' => true, 'target_team_id' => $blauw->id],
            ['number' => 24, 'category' => 'Nacht & verrassing', 'points' => 15, 'title' => 'Fotobom', 'description' => 'Sluip ongezien op de achtergrond van een selfie die een toerist ergens van zichzelf maakt — bewijs van beide foto\'s nodig.'],
        ];

        foreach ($challenges as $challenge) {
            Challenge::query()->firstOrCreate(
                ['number' => $challenge['number']],
                $challenge,
            );
        }
    }
}
