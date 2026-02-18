<?php

namespace Database\Seeders;

use App\Models\DocuMentor\GroupName;
use Illuminate\Database\Seeder;

class GroupNameSeeder extends Seeder
{
    /**
     * Seed group names (genz_word + tech_word) for Docu Mentor project groups.
     * department_id = null means global; any department can use these.
     */
    public function run(): void
    {
        $pairs = [
            ['Chale', 'Compiler'], ['Massa', 'Debugger'], ['Eii', 'IDE'], ['Ajeee', 'Codebase'],
            ['Aswear', 'Syntax'], ['NoDull', 'Object'], ['Demure', 'Class'], ['SoftLife', 'Function'],
            ['Pressure', 'Variable'], ['Yawa', 'Loop'], ['Vhim', 'Recursion'], ['TooSure', 'Module'],
            ['Ebefa', 'Repository'], ['EdeyBee', 'VersionControl'], ['Wossop', 'Git'], ['Eish', 'Branch'],
            ['Herh', 'Commit'], ['LaVie', 'Merge'], ['Kasa', 'Build'], ['Sika', 'Deployment'],
            ['BigMood', 'Runtime'], ['Bet', 'Exception'], ['Cap', 'Refactor'], ['NoCap', 'Test'],
            ['Periodt', 'UnitTest'], ['Slay', 'Script'], ['Litty', 'Patch'], ['VibesOn', 'Algorithm'],
            ['Squad', 'Framework'], ['Hype', 'API'], ['Steeze', 'Lambda'], ['Cruise', 'Object-Oriented'],
            ['Bounce', 'MemoryManagement'], ['Chillax', 'DataStructure'], ['Sharp', 'DesignPattern'],
            ['Lowkey', 'SourceCode'], ['Highkey', 'GitHub'], ['Wavy', 'Push'], ['Savage', 'Pull'],
            ['MadTing', 'ContinuousIntegration'], ['TopTier', 'ContinuousDelivery'], ['Vibe', 'Agile'],
            ['Flex', 'Sprint'], ['Glow', 'IP Address'], ['NextGen', 'DNS'], ['Alpha', 'Gateway'],
            ['Sigma', 'Switch'], ['Ghost', 'Router'], ['Stealth', 'Subnet'], ['Flash', 'VPN'],
            ['Storm', 'Packet'], ['Blaze', 'Protocol'], ['Chale', 'Bandwidth'], ['Massa', 'Firewall'],
            ['Eii', 'SSL'], ['Ajeee', 'TLS'], ['Aswear', 'NAT'], ['NoDull', 'TCP'],
            ['Demure', 'UDP'], ['SoftLife', 'IPv6'], ['Pressure', 'Proxy'], ['Yawa', 'HTTP'],
            ['Vhim', 'HTTPS'], ['TooSure', 'SSH'], ['Ebefa', 'Ports'], ['EdeyBee', 'Ping'],
            ['Wossop', 'Throughput'], ['Eish', 'Latency'], ['Herh', 'LoadBalancer'], ['LaVie', 'Routing'],
            ['Kasa', 'Traceroute'], ['Sika', 'DataLink'], ['BigMood', 'MAC Address'], ['Bet', 'MTU'],
            ['Cap', 'IPsec'], ['NoCap', 'BGP'], ['Periodt', 'DNS Server'], ['Slay', 'DHCP'],
            ['Litty', 'FiberOptic'], ['VibesOn', 'LAN'], ['Squad', 'WAN'], ['Hype', 'VLAN'],
            ['Steeze', 'API Gateway'], ['Cruise', 'HTML'], ['Bounce', 'CSS'], ['Chillax', 'JavaScript'],
            ['Sharp', 'React'], ['Lowkey', 'Angular'], ['Highkey', 'Vue'], ['Wavy', 'Node.js'],
            ['Savage', 'Express'], ['MadTing', 'REST'], ['TopTier', 'WebSocket'], ['Vibe', 'AJAX'],
            ['Flex', 'JQuery'], ['Glow', 'Bootstrap'], ['NextGen', 'SASS'], ['Alpha', 'Webpack'],
            ['Sigma', 'JSON'], ['Ghost', 'API'], ['Stealth', 'HTTP/2'], ['Flash', 'WebAssembly'],
            ['Storm', 'CDN'], ['Blaze', 'Frontend'], ['Chale', 'Backend'], ['Massa', 'FullStack'],
            ['Eii', 'ProgressiveWebApp'], ['Ajeee', 'SPA'], ['Aswear', 'Authentication'], ['NoDull', 'Authorization'],
            ['Demure', 'JWT'], ['SoftLife', 'OAuth'], ['Pressure', 'CORS'], ['Yawa', 'CrossOrigin'],
            ['Vhim', 'SEO'], ['TooSure', 'Sitemap'], ['Ebefa', 'ContentManagement'], ['EdeyBee', 'CMS'],
            ['Wossop', 'WebRTC'], ['Eish', 'CSS Grid'], ['Herh', 'Flexbox'], ['LaVie', 'Serverless'],
            ['Kasa', 'DataFrame'], ['Sika', 'Numpy'], ['BigMood', 'Pandas'], ['Bet', 'Matplotlib'],
            ['Cap', 'Seaborn'], ['NoCap', 'TensorFlow'], ['Periodt', 'PyTorch'], ['Slay', 'Scikit-Learn'],
            ['Litty', 'Keras'], ['VibesOn', 'Neural Network'], ['Squad', 'DeepLearning'], ['Hype', 'MachineLearning'],
            ['Steeze', 'SupervisedLearning'], ['Cruise', 'UnsupervisedLearning'], ['Bounce', 'ReinforcementLearning'],
            ['Chillax', 'Model'], ['Sharp', 'Algorithm'], ['Lowkey', 'Classification'], ['Highkey', 'Regression'],
            ['Wavy', 'Clustering'], ['Savage', 'CrossValidation'], ['MadTing', 'Accuracy'], ['TopTier', 'Precision'],
            ['Vibe', 'Recall'], ['Flex', 'F1-Score'], ['Glow', 'Hyperparameter'], ['NextGen', 'GridSearch'],
            ['Alpha', 'FeatureEngineering'], ['Sigma', 'PCA'], ['Ghost', 'Clustering'], ['Stealth', 'KNN'],
            ['Flash', 'DecisionTree'], ['Storm', 'RandomForest'], ['Blaze', 'SVM'], ['Chale', 'GradientBoosting'],
            ['Massa', 'FeatureSelection'], ['Eii', 'LossFunction'], ['Ajeee', 'TrainingSet'], ['Aswear', 'TestSet'],
            ['NoDull', 'Dataset'], ['Demure', 'SQL'], ['SoftLife', 'NoSQL'], ['Pressure', 'RelationalDatabase'],
            ['Yawa', 'NonRelationalDB'], ['Vhim', 'Indexing'], ['TooSure', 'Query'], ['Ebefa', 'JOIN'],
            ['EdeyBee', 'Schema'], ['Wossop', 'PrimaryKey'], ['Eish', 'ForeignKey'], ['Herh', 'EntityRelationship'],
            ['LaVie', 'Normalization'], ['Kasa', 'Denormalization'], ['Sika', 'ACID'], ['BigMood', 'Transactions'],
            ['Bet', 'Backup'], ['Cap', 'DataWarehouse'], ['NoCap', 'DataLake'], ['Periodt', 'MongoDB'],
            ['Slay', 'PostgreSQL'], ['Litty', 'MySQL'], ['VibesOn', 'SQLite'], ['Squad', 'Cassandra'],
            ['Hype', 'Redis'], ['Steeze', 'Firebase'], ['Cruise', 'OracleDB'], ['Bounce', 'Index'],
            ['Chillax', 'Sharding'], ['Sharp', 'Table'], ['Lowkey', 'Row'], ['Highkey', 'Column'],
            ['Wavy', 'SQL Injection'], ['Savage', 'CRUD'], ['MadTing', 'DataReplication'], ['TopTier', 'DataConsistency'],
            ['Vibe', 'SQLAlchemy'], ['Flex', 'DBMS'],
        ];

        foreach ($pairs as $pair) {
            GroupName::firstOrCreate(
                [
                    'department_id' => null,
                    'genz_word' => $pair[0],
                    'tech_word' => $pair[1],
                ],
                ['department_id' => null, 'genz_word' => $pair[0], 'tech_word' => $pair[1]]
            );
        }
    }
}
