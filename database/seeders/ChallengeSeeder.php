<?php

namespace Database\Seeders;

use App\Models\Challenge;
use App\Models\Team;
use Illuminate\Database\Seeder;

class ChallengeSeeder extends Seeder
{
    /**
     * Weekend Assinghausen: vrijdag 2 t/m maandag 5 oktober 2026 (lokale tijd).
     *
     * Elke opdracht krijgt hier een vast `release_at`. De scheduler
     * (`game:release-due`) geeft 'm automatisch vrij zodra dat moment
     * verstreken is — dit weekend hoeft niemand dus zelf op de knop te
     * drukken. Er is bewust geen `deadline_at`: opdrachten verlopen niet
     * automatisch.
     */
    public function run(): void
    {
        $rood = Team::query()->where('name', 'Rood')->firstOrFail();
        $blauw = Team::query()->where('name', 'Blauw')->firstOrFail();

        $challenges = [
            // Vrijdagavond 2 oktober — na aankomst.
            ['number' => 1, 'category' => 'Eten & drinken', 'points' => 10, 'title' => 'McDrive te voet', 'description' => 'Bestel en haal een compleet menu op bij de McDrive — lopend of fietsend, niet met de auto. Foto bij het loket.', 'release_at' => '2026-10-02 18:00'],
            ['number' => 3, 'category' => 'Eten & drinken', 'points' => 15, 'title' => 'Sauerlandse snack', 'description' => 'Zoek een typisch Duitse/regionale snack op (bijv. Bratwurst) en film ieders eerste reactie bij de eerste hap.', 'release_at' => '2026-10-02 19:30'],
            ['number' => 9, 'category' => 'Sociaal', 'points' => 10, 'title' => 'Handtekeningenjacht', 'description' => 'Verzamel de handtekening van drie wildvreemden op een blaadje, met foto van het moment zelf.', 'release_at' => '2026-10-02 21:00'],

            // Zaterdag 3 oktober overdag.
            ['number' => 17, 'category' => 'Creatief & in huis', 'points' => 10, 'title' => 'Recordpoging opruimen', 'description' => 'Ruim de keuken of huiskamer binnen 5 minuten spik en span op — voor- en na-foto.', 'release_at' => '2026-10-03 09:00'],
            ['number' => 19, 'category' => 'Tussen de teams', 'points' => 10, 'title' => 'Verrassingsontbijt', 'description' => 'Bereid voor het andere team een ontbijtje en serveer het met een oprecht compliment — gefilmd.', 'release_at' => '2026-10-03 10:00'],
            ['number' => 5, 'category' => 'Sportief & buiten', 'points' => 15, 'title' => 'Bergtop selfie', 'description' => 'Beklim de dichtstbijzijnde uitkijktoren of heuveltop en maak daar een groepsfoto met uitzicht.', 'release_at' => '2026-10-03 11:30'],
            ['number' => 2, 'category' => 'Eten & drinken', 'points' => 10, 'title' => 'Onbekend biertje', 'description' => 'Koop bij de plaatselijke supermarkt of kroeg een bier dat niemand kent, proef het samen en geef live een rapportcijfer.', 'release_at' => '2026-10-03 13:00'],
            ['number' => 6, 'category' => 'Sportief & buiten', 'points' => 10, 'title' => 'Blinde gids', 'description' => 'Eén teamlid wordt geblinddoekt en alleen met verbale aanwijzingen door de rest een klein parcours geleid.', 'release_at' => '2026-10-03 14:30'],
            ['number' => 14, 'category' => 'Creatief & in huis', 'points' => 10, 'title' => 'Twijgentoren', 'description' => 'Bouw met alleen materiaal dat je buiten vindt een toren die minimaal 1 meter hoog blijft staan — 10 seconden vastgehouden op camera.', 'release_at' => '2026-10-03 16:00'],
            ['number' => 12, 'category' => 'Sociaal', 'points' => 10, 'title' => 'Woordje Duits', 'description' => 'Leer een lokale een Duitse zin of dialectwoord en herhaal \'m correct op camera.', 'release_at' => '2026-10-03 17:30'],
            ['number' => 10, 'category' => 'Sociaal', 'points' => 15, 'title' => 'Straatzanger', 'description' => 'Zing gezamenlijk een couplet voor een winkelmedewerker of voorbijganger — gefilmd.', 'release_at' => '2026-10-03 19:00'],
            ['number' => 15, 'category' => 'Creatief & in huis', 'points' => 15, 'title' => 'Levend standbeeld', 'description' => 'Twee teamleden staan 5 minuten doodstil geposeerd op een opvallende plek, gefilmd door de rest.', 'release_at' => '2026-10-03 20:30'],

            // Zaterdagavond/-nacht — de twee expliciete nachtopdrachten.
            ['number' => 8, 'category' => 'Sportief & buiten', 'points' => 15, 'title' => 'Zaklamptikkertje', 'description' => "Speel 's avonds 10 minuten verstoppertje met zaklampen tussen beide teams — winnaar filmt de beslissende tik.", 'release_at' => '2026-10-03 22:00'],
            ['number' => 21, 'category' => 'Nacht & verrassing', 'points' => 20, 'title' => 'Middernachtmars', 'description' => 'Tussen 00:00–02:00 loopt het hele team naar een vooraf aangewezen punt in de buurt en fotografeert daar iets unieks.', 'release_at' => '2026-10-04 00:30'],

            // Zondag 4 oktober overdag.
            ['number' => 16, 'category' => 'Creatief & in huis', 'points' => 5, 'title' => 'Linkshandig portret', 'description' => 'Teken met je niet-dominante hand in 3 minuten een portret van een teamgenoot; hij raadt zelf wie het is.', 'release_at' => '2026-10-04 09:00'],
            ['number' => 7, 'category' => 'Sportief & buiten', 'points' => 10, 'title' => 'Bank af', 'description' => 'Rol iemand veilig een glooiend grasveld af — getimed en gefilmd.', 'release_at' => '2026-10-04 10:30'],
            ['number' => 4, 'category' => 'Eten & drinken', 'points' => 5, 'title' => 'Geen handen', 'description' => 'Eet een compleet tussendoortje zonder je handen te gebruiken — op video.', 'release_at' => '2026-10-04 12:00'],
            ['number' => 11, 'category' => 'Sociaal', 'points' => 20, 'title' => 'Drie keer ruilen', 'description' => 'Begin met een klein voorwerp en ruil het bij vreemden drie keer achter elkaar voor iets anders. Foto van elke ruil.', 'release_at' => '2026-10-04 13:30'],
            ['number' => 13, 'category' => 'Sociaal', 'points' => 15, 'title' => 'Verkooppraatje', 'description' => 'Verkoop binnen 5 minuten (symbolisch, geen geld nodig) een voorwerp uit het vakantiehuis aan een vreemde — gefilmde pitch.', 'release_at' => '2026-10-04 15:00'],
            ['number' => 18, 'category' => 'Creatief & in huis', 'points' => 15, 'title' => 'Groepschoreografie', 'description' => 'Bedenk in 15 minuten een dansroutine op een zelfgekozen nummer en voer \'m gezamenlijk uit in één take.', 'release_at' => '2026-10-04 16:30'],
            ['number' => 20, 'category' => 'Tussen de teams', 'points' => 10, 'title' => 'Pokerface', 'description' => 'Elk teamlid vertelt om de beurt een overduidelijke leugen met stalen gezicht; het andere team raadt wie loog.', 'release_at' => '2026-10-04 18:00'],
            ['number' => 24, 'category' => 'Nacht & verrassing', 'points' => 15, 'title' => 'Fotobom', 'description' => 'Sluip ongezien op de achtergrond van een selfie die een toerist ergens van zichzelf maakt — bewijs van beide foto\'s nodig.', 'release_at' => '2026-10-04 19:30'],
            ['number' => 22, 'category' => 'Nacht & verrassing', 'points' => 25, 'title' => 'Geheime bonusopdracht A', 'description' => 'Wordt willekeurig alleen naar Team Rood gestuurd — Team Blauw weet niet dat \'m bestaat.', 'is_secret' => true, 'target_team_id' => $rood->id, 'release_at' => '2026-10-04 21:00'],
            ['number' => 23, 'category' => 'Nacht & verrassing', 'points' => 25, 'title' => 'Geheime bonusopdracht B', 'description' => 'Wordt willekeurig alleen naar Team Blauw gestuurd — Team Rood weet niet dat \'m bestaat.', 'is_secret' => true, 'target_team_id' => $blauw->id, 'release_at' => '2026-10-04 21:00'],
        ];

        foreach ($challenges as $challenge) {
            Challenge::query()->firstOrCreate(
                ['number' => $challenge['number']],
                $challenge,
            );
        }
    }
}
